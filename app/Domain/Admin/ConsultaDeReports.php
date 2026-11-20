<?php

namespace App\Domain\Admin;

use App\Enums\MotivoDoReport;
use App\Enums\StatusDoReport;
use App\Models\Admin;
use App\Models\Questao;
use App\Models\ReportQuestao;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Fila de reports agrupada por questão. O aluno aparece só pelo apelido. */
class ConsultaDeReports
{
    public function __construct(private readonly Auditoria $auditoria) {}

    /** @return array<string, int> */
    public function contagemPorStatus(): array
    {
        $contagem = DB::table('reports_questao')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(StatusDoReport::cases())->mapWithKeys(fn (StatusDoReport $s) => [$s->value => (int) ($contagem[$s->value] ?? 0)])->all();
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function fila(StatusDoReport $status): LengthAwarePaginator
    {
        return DB::table('reports_questao as r')
            ->join('questoes as q', 'q.id', '=', 'r.questao_id')
            ->leftJoin('assuntos as a', 'a.id', '=', 'q.assunto_id')->leftJoin('materias as m', 'm.id', '=', 'a.materia_id')
            ->where('r.status', $status->value)
            ->selectRaw('q.id, q.codigo, q.origem, a.nome as assunto, m.nome as materia, count(*) as total, max(r.created_at) as ultimo_em')
            ->groupBy('q.id', 'q.codigo', 'q.origem', 'a.nome', 'm.nome')
            ->orderByDesc('total')->orderByDesc('ultimo_em')
            ->paginate((int) config('gabaritu.admin.por_pagina'))->withQueryString();
    }

    /**
     * @param  Collection<int, stdClass>  $questoes
     * @return array<int, array<int, array<string, mixed>>> reports da página, por questão
     */
    public function reportsDe(Collection $questoes, StatusDoReport $status): array
    {
        return DB::table('reports_questao as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereIn('r.questao_id', $questoes->pluck('id')->all())->where('r.status', $status->value)
            ->orderBy('r.created_at')->select('r.id', 'r.questao_id', 'r.motivo', 'r.comentario', 'r.status', 'r.created_at', 'u.apelido')
            ->get()->groupBy('questao_id')
            ->map(fn (Collection $linhas) => $linhas->map(fn (stdClass $r) => [
                'id' => (int) $r->id,
                'apelido' => $r->apelido,
                'motivo' => $r->motivo,
                'comentario' => $r->comentario,
                'status' => $r->status,
                'criado_em' => Fuso::data(Carbon::parse($r->created_at, config('app.timezone'))),
                'url' => route('admin.reports.update', $r->id, absolute: false),
            ])->values()->all())->all();
    }

    /** @return array<string, int> contagem por motivo (todos os motivos, com zero) */
    public function porMotivo(int $questaoId, StatusDoReport $status): array
    {
        $contagem = DB::table('reports_questao')->where('questao_id', $questaoId)->where('status', $status->value)
            ->selectRaw('motivo, count(*) as total')->groupBy('motivo')->pluck('total', 'motivo');

        return collect(MotivoDoReport::cases())->mapWithKeys(fn (MotivoDoReport $m) => [$m->value => (int) ($contagem[$m->value] ?? 0)])->all();
    }

    public function atualizar(Admin $admin, ReportQuestao $report, StatusDoReport $para): void
    {
        $de = $report->status;
        if ($de === $para->value) {
            return;
        }

        $report->update(['status' => $para->value, 'resolvido_em' => $para === StatusDoReport::Aberto ? null : now()]);
        $this->auditoria->registrar($admin, AcaoDeAuditoria::ReportAtualizado, $report, detalhes: ['de' => $de, 'para' => $para->value, 'questao_id' => $report->questao_id]);
    }

    /** Muda de uma vez todos os reports abertos da questão (ex.: depois de corrigir o enunciado). */
    public function atualizarDaQuestao(Admin $admin, Questao $questao, StatusDoReport $para): int
    {
        $total = ReportQuestao::query()->where('questao_id', $questao->id)->where('status', StatusDoReport::Aberto->value)
            ->update(['status' => $para->value, 'resolvido_em' => now()]);

        if ($total > 0) {
            $this->auditoria->registrar($admin, AcaoDeAuditoria::ReportAtualizado, $questao, detalhes: ['de' => 'aberto', 'para' => $para->value, 'quantidade' => $total]);
        }

        return $total;
    }
}
