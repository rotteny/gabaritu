<?php

namespace App\Domain\Metricas;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Contadores diários agregados (docs/admin.md). Nada de IP, user-agent ou id é gravado.
 *
 * - Cada incremento é um upsert atômico (`on conflict do update set x = x + 1`), sem ler antes.
 * - O trabalho roda depois que a resposta foi enviada (terminating): não atrasa o aluno e nunca o derruba.
 * - Aluno ativo único por dia: um token HMAC (que muda por dia) entra no cache com TTL; se já estava lá, não conta de novo.
 *   O cache expira sozinho e o token não aponta para o aluno sem a chave do app.
 */
class RegistradorDeMetricas
{
    private const int TTL_DO_CONJUNTO_EM_HORAS = 36;

    private const string CHAVE_NA_SESSAO = 'metricas.ativo_no_dia';

    public function login(User $aluno): void
    {
        $this->depoisDaResposta(function () use ($aluno): void {
            $this->incrementar(Metrica::Logins);
            $this->marcarAtivo($aluno);
        });
    }

    public function resposta(User $aluno): void
    {
        $this->depoisDaResposta(function () use ($aluno): void {
            $this->incrementar(Metrica::Respostas);
            $this->marcarAtivo($aluno);
        });
    }

    public function visitaNaLanding(): void
    {
        $this->depoisDaResposta(fn () => $this->incrementar(Metrica::VisitasLanding));
    }

    /**
     * Conta a exclusão no mês (só o número). Roda dentro da transação que apaga a conta: se ela desfaz, o contador também.
     */
    public function contaApagada(User $conta): void
    {
        $coluna = $conta->ehResponsavel() ? 'responsaveis' : 'alunos';
        $mes = now(config('gabaritu.fuso'))->startOfMonth()->format('Y-m-d');

        DB::statement(
            "insert into contas_apagadas_mes (mes, {$coluna}) values (?, 1) on conflict (mes) do update set {$coluna} = contas_apagadas_mes.{$coluna} + 1",
            [$mes],
        );
    }

    public function incrementar(Metrica $metrica, ?string $dia = null): void
    {
        $coluna = $metrica->value;

        DB::statement(
            "insert into metricas_diarias (dia, {$coluna}) values (?, 1) on conflict (dia) do update set {$coluna} = metricas_diarias.{$coluna} + 1",
            [$dia ?? $this->hoje()],
        );
    }

    /** Conta o aluno uma única vez por dia (login ou resposta, o que vier primeiro). */
    public function marcarAtivo(User $aluno): void
    {
        $dia = $this->hoje();
        $sessao = app()->bound('session.store') ? session() : null;

        if ($sessao?->get(self::CHAVE_NA_SESSAO) === $dia) {
            return;
        }

        $chave = "metricas:ativo:{$dia}:".$this->token($aluno, $dia);

        if (Cache::add($chave, 1, now()->addHours(self::TTL_DO_CONJUNTO_EM_HORAS))) {
            $this->incrementar(Metrica::AlunosAtivos, $dia);
        }

        $sessao?->put(self::CHAVE_NA_SESSAO, $dia);
    }

    private function token(User $aluno, string $dia): string
    {
        return substr(hash_hmac('sha256', (string) $aluno->id, $dia.'|'.config('app.key')), 0, 32);
    }

    private function hoje(): string
    {
        return now(config('gabaritu.fuso'))->format('Y-m-d');
    }

    private function depoisDaResposta(callable $trabalho): void
    {
        $feito = false;

        app()->terminating(function () use ($trabalho, &$feito): void {
            if ($feito) {
                return;
            }
            $feito = true;

            try {
                $trabalho();
            } catch (Throwable $erro) {
                report($erro);
            }
        });
    }
}
