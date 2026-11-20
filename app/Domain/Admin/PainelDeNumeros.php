<?php

namespace App\Domain\Admin;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Números do dashboard: cartões, funil e séries diárias. Tudo agregado no banco. */
class PainelDeNumeros
{
    private const int DIAS_DA_SEMANA = 7;

    private const int DIAS_DO_MES = 30;

    private const int ALUNOS_MINIMO_DE_DIAS_PARA_VOLTAR = 2;

    /** @return array<string, mixed> */
    public function cartoes(): array
    {
        $hoje = Fuso::hoje()->format('Y-m-d');
        $metricasDeHoje = DB::table('metricas_diarias')->where('dia', $hoje)->first();

        return [
            'alunos' => ['total' => $this->alunos()->count(), 'por_status' => $this->alunosPorStatus()],
            'responsaveis' => User::query()->where('tipo', TipoConta::Responsavel->value)->count(),
            'cadastros' => $this->cadastrosRecentes(),
            'ativos' => ['hoje' => (int) ($metricasDeHoje->alunos_ativos ?? 0), 'ultimos_7_dias' => $this->ativosNaSemana()],
            'respostas_hoje' => (int) ($metricasDeHoje->respostas ?? 0),
        ];
    }

    /**
     * Cadastro → liberado (adulto, ou menor com responsável que autorizou) → primeira resposta → voltou em outro dia.
     * Parte dos cadastros feitos dentro do período.
     *
     * @return array<string, mixed>
     */
    public function funil(Periodo $periodo): array
    {
        $inicio = $periodo->inicio($this->primeiroCadastro())->setTimezone(config('app.timezone'));
        $maioridade = (int) Fuso::hoje()->format('Y') - User::IDADE_MAIORIDADE;
        $dias = Fuso::diaSql('r.respondida_em');

        $linha = $this->alunos()->where('created_at', '>=', $inicio)->selectRaw(
            'count(*) as cadastro, ' // @phpstan-ignore argument.type (SQL só com constantes e a expressão de dia da classe Fuso)
            .'count(*) filter (where ano_nascimento > ?) as menores, '
            .'count(*) filter (where ano_nascimento > ? and status = ?) as menores_liberados, '
            .'count(*) filter (where status = ?) as liberados, '
            .'count(*) filter (where exists (select 1 from respostas r where r.user_id = users.id)) as com_resposta, '
            ."count(*) filter (where (select count(distinct {$dias}) from respostas r where r.user_id = users.id) >= ?) as voltaram",
            [$maioridade - 1, $maioridade - 1, StatusConta::Ativo->value, StatusConta::Ativo->value, self::ALUNOS_MINIMO_DE_DIAS_PARA_VOLTAR],
        )->toBase()->first();

        return [
            'etapas' => [
                ['chave' => 'cadastro', 'rotulo' => 'Cadastro', 'valor' => (int) $linha->cadastro],
                ['chave' => 'liberado', 'rotulo' => 'Liberado (adulto ou responsável autorizou)', 'valor' => (int) $linha->liberados],
                ['chave' => 'primeira_resposta', 'rotulo' => 'Primeira resposta', 'valor' => (int) $linha->com_resposta],
                ['chave' => 'voltou', 'rotulo' => 'Voltou em outro dia', 'valor' => (int) $linha->voltaram],
            ],
            'menores' => ['cadastrados' => (int) $linha->menores, 'autorizados' => (int) $linha->menores_liberados],
        ];
    }

    /**
     * Uma linha por dia do período, com zeros nos dias sem registro.
     *
     * @return list<array{dia: string, cadastros: int, alunos_ativos: int, logins: int, respostas: int, visitas_landing: int}>
     */
    public function series(Periodo $periodo): array
    {
        $inicio = $periodo->inicio($this->primeiroCadastro());
        $cadastros = $this->cadastrosPorDia($inicio);
        $metricas = DB::table('metricas_diarias')->where('dia', '>=', $inicio->format('Y-m-d'))->get()->keyBy('dia');

        $linhas = [];
        for ($dia = $inicio; $dia <= Fuso::hoje(); $dia = $dia->addDay()) {
            $chave = $dia->format('Y-m-d');
            $metrica = $metricas->get($chave);
            $linhas[] = [
                'dia' => $chave,
                'cadastros' => (int) ($cadastros[$chave] ?? 0),
                'alunos_ativos' => (int) ($metrica->alunos_ativos ?? 0),
                'logins' => (int) ($metrica->logins ?? 0),
                'respostas' => (int) ($metrica->respostas ?? 0),
                'visitas_landing' => (int) ($metrica->visitas_landing ?? 0),
            ];
        }

        return $linhas;
    }

    /** @return Builder<User> */
    private function alunos(): Builder
    {
        return User::query()->where('tipo', TipoConta::Aluno->value);
    }

    /** @return array<string, int> */
    private function alunosPorStatus(): array
    {
        $contagem = $this->alunos()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(StatusConta::cases())->mapWithKeys(fn (StatusConta $s) => [$s->value => (int) ($contagem[$s->value] ?? 0)])->all();
    }

    /** @return array{hoje: int, ultimos_7_dias: int, ultimos_30_dias: int} */
    private function cadastrosRecentes(): array
    {
        $linha = $this->alunos()->selectRaw(
            'count(*) filter (where created_at >= ?) as hoje, count(*) filter (where created_at >= ?) as semana, count(*) filter (where created_at >= ?) as mes',
            [Fuso::inicioDoDiaEmUtc(), Fuso::inicioDoDiaEmUtc(self::DIAS_DA_SEMANA - 1), Fuso::inicioDoDiaEmUtc(self::DIAS_DO_MES - 1)],
        )->toBase()->first();

        return ['hoje' => (int) $linha->hoje, 'ultimos_7_dias' => (int) $linha->semana, 'ultimos_30_dias' => (int) $linha->mes];
    }

    /** Alunos distintos que entraram ou responderam desde 6 dias atrás (a semana inclui hoje). */
    private function ativosNaSemana(): int
    {
        $desde = Fuso::inicioDoDiaEmUtc(self::DIAS_DA_SEMANA - 1);

        return $this->alunos()->where(function (Builder $q) use ($desde): void {
            $q->where('ultimo_acesso_em', '>=', $desde)
                ->orWhereExists(fn ($r) => $r->selectRaw('1')->from('respostas')->whereColumn('respostas.user_id', 'users.id')->where('respondida_em', '>=', $desde));
        })->count();
    }

    /** @return array<string, int> */
    private function cadastrosPorDia(CarbonImmutable $inicio): array
    {
        return $this->alunos()->toBase()
            ->where('created_at', '>=', $inicio->setTimezone(config('app.timezone')))
            // @phpstan-ignore argument.type (expressão fixa da classe Fuso)
            ->selectRaw(Fuso::diaSql('created_at').' as dia, count(*) as total')
            ->groupBy('dia')->pluck('total', 'dia')
            ->map(fn ($total) => (int) $total)->all();
    }

    private function primeiroCadastro(): ?CarbonImmutable
    {
        $primeiro = $this->alunos()->min('created_at');

        return $primeiro === null ? null : CarbonImmutable::parse($primeiro, config('app.timezone'));
    }
}
