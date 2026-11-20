<?php

namespace App\Domain\Admin;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use App\Support\EmailMascarado;
use Illuminate\Support\Facades\DB;

/** O que precisa de atenção do dono por causa do ECA Digital e da LGPD. */
class PendenciasLegais
{
    /** @return array<string, mixed> */
    public function resumo(): array
    {
        $limite = (int) config('gabaritu.admin.itens_por_lista');

        return [
            'autorizacoes_paradas' => $this->autorizacoesParadas($limite),
            'convites_expirados' => $this->convitesExpirados(),
            'contestacoes' => $this->contestacoes($limite),
            'pedidos_de_responsavel' => $this->pedidosPendentes($limite),
            'contas_apagadas_por_mes' => $this->contasApagadas(),
        ];
    }

    /** @return array{dias: int, total: int, itens: array<int, array<string, mixed>>} */
    private function autorizacoesParadas(int $limite): array
    {
        $dias = (int) config('gabaritu.admin.autorizacao_parada_dias');
        $consulta = User::query()->where('tipo', TipoConta::Aluno->value)->where('status', StatusConta::AguardandoResponsavel->value)
            ->where('pendente_desde', '<', now()->subDays($dias));

        return [
            'dias' => $dias,
            'total' => (clone $consulta)->count(),
            'itens' => $consulta->orderBy('pendente_desde')->limit($limite)->get()->map(fn (User $a) => [
                'id' => $a->id,
                'apelido' => $a->apelido,
                'parado_ha_dias' => (int) $a->pendente_desde?->diffInDays(now()),
                'email_responsavel_mascarado' => $a->email_responsavel ? EmailMascarado::de($a->email_responsavel) : null,
                'url' => route('admin.alunos.show', $a, absolute: false),
            ])->all(),
        ];
    }

    private function convitesExpirados(): int
    {
        return ConviteResponsavel::query()->whereNull('usado_em')->whereNull('recusado_em')->whereNull('invalidado_em')->where('expira_em', '<', now())->count();
    }

    /** @return array{total: int, itens: array<int, array<string, mixed>>} */
    private function contestacoes(int $limite): array
    {
        $consulta = VinculoResponsavel::query()->where('status', VinculoResponsavel::SUSPENSO);

        return [
            'total' => (clone $consulta)->count(),
            'itens' => $consulta->with('aluno:id,apelido')->orderBy('suspenso_em')->limit($limite)->get()->map(fn (VinculoResponsavel $v) => [
                'aluno_id' => $v->aluno_id,
                'aluno' => $v->aluno?->apelido,
                'responsavel_url' => route('admin.responsaveis.show', $v->responsavel_id, absolute: false),
                'aluno_url' => route('admin.alunos.show', $v->aluno_id, absolute: false),
                'parentesco' => $v->parentesco,
                'desde' => Fuso::data($v->suspenso_em),
            ])->all(),
        ];
    }

    /** @return array{total: int, itens: array<int, array<string, mixed>>} */
    private function pedidosPendentes(int $limite): array
    {
        $consulta = ConviteResponsavel::query()->pendentes()->whereIn('tipo', TipoConvite::pedidos());

        return [
            'total' => (clone $consulta)->count(),
            'itens' => $consulta->orderBy('created_at')->limit($limite)->get()->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'email_mascarado' => EmailMascarado::de($c->email),
                'parentesco' => $c->parentesco,
                'criado_em' => Fuso::data($c->created_at),
                'expira_em' => Fuso::data($c->expira_em),
            ])->all(),
        ];
    }

    /** @return array<int, array{mes: string, alunos: int, responsaveis: int}> */
    private function contasApagadas(): array
    {
        return DB::table('contas_apagadas_mes')->orderByDesc('mes')->limit(24)->get()
            ->map(fn (object $l) => ['mes' => substr((string) $l->mes, 0, 7), 'alunos' => (int) $l->alunos, 'responsaveis' => (int) $l->responsaveis])->all();
    }
}
