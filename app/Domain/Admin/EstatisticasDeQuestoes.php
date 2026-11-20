<?php

namespace App\Domain\Admin;

use App\Enums\StatusDoReport;
use App\Models\AdminAuditoria;
use App\Models\Questao;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Estatísticas de questão para o admin. Os números vêm de uma subconsulta por questão
 * (respostas agrupadas por alternativa) e as "suspeitas" são decididas em SQL, para filtrar e paginar no banco.
 */
class EstatisticasDeQuestoes
{
    public const string JURIDICO_PENDENTE = 'pendente';

    /** @var list<string> */
    public const array STATUS_JURIDICO = [self::JURIDICO_PENDENTE, 'liberada', Questao::JURIDICO_REMOVER];

    private const int CASAS_DA_PORCENTAGEM = 1;

    /** @return array{materias: array<int, array<string, mixed>>, suspeitas: int} */
    public function resumoPorMateriaEAssunto(): array
    {
        $linhas = DB::table('assuntos as a')
            ->join('materias as m', 'm.id', '=', 'a.materia_id')
            ->leftJoin('questoes as q', 'q.assunto_id', '=', 'a.id')
            ->leftJoinSub($this->estatisticas(), 'e', 'e.questao_id', '=', 'q.id')
            ->selectRaw(
                'm.id as materia_id, m.nome as materia, a.id as assunto_id, a.nome as assunto, count(q.id) as total, '
                .'count(q.id) filter (where q.origem = ?) as autorais, count(q.id) filter (where q.origem = ?) as inep, '
                .'coalesce(sum(e.total), 0) as respostas, coalesce(sum(e.acertos), 0) as acertos',
                [Questao::ORIGEM_AUTORAL, Questao::ORIGEM_INEP],
            )
            ->groupBy('m.id', 'm.nome', 'm.ordem', 'a.id', 'a.nome', 'a.ordem')
            ->orderBy('m.ordem')->orderBy('a.ordem')->get();

        return [
            'materias' => $linhas->groupBy('materia_id')->map(fn (Collection $assuntos) => [
                'id' => $assuntos->first()->materia_id,
                'nome' => $assuntos->first()->materia,
                ...$this->somar($assuntos),
                'assuntos' => $assuntos->map(fn ($a) => ['id' => $a->assunto_id, 'nome' => $a->assunto, ...$this->somar(collect([$a]))])->values()->all(),
            ])->values()->all(),
            'suspeitas' => $this->suspeitasTotal(),
        ];
    }

    /**
     * @param  array{origem?: ?string, materia?: ?string, assunto?: ?string, status_juridico?: ?string, suspeita?: ?string}  $filtros
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $pagina = $this->consultaDaLista($filtros)->orderBy('q.codigo')->paginate((int) config('gabaritu.admin.por_pagina'))->withQueryString();
        $distribuicao = $this->distribuicao(array_values(array_map(intval(...), $pagina->getCollection()->pluck('id')->all())));

        return $pagina->through(fn ($q) => $this->linha($q, $distribuicao[$q->id] ?? []));
    }

    /** Todas as questões com os números (sem paginar), para o CSV agregado.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function todasParaExportar(): Collection
    {
        return $this->consultaDaLista([])->orderBy('q.codigo')->get()->map(fn (stdClass $q) => $this->linha($q, []));
    }

    /** @return array<string, mixed> */
    public function ficha(Questao $questao): array
    {
        $linha = $this->consultaDaLista([])->where('q.id', $questao->id)->first();
        $resumo = $this->linha($linha, $this->distribuicao([$questao->id])[$questao->id] ?? []);
        $textos = $questao->alternativas()->with('etiqueta:id,nome')->get()->keyBy('letra');

        $resumo['distribuicao'] = array_map(fn (array $d) => $d + ['texto' => $textos[$d['letra']]->texto ?? null, 'etiqueta' => $textos[$d['letra']]->etiqueta->nome ?? null], $resumo['distribuicao']);

        return $resumo + [
            'dificuldade' => (int) $questao->dificuldade,
            'status' => $questao->status,
            'contexto' => $questao->contexto,
            'enunciado' => $questao->enunciado,
            'explicacao' => $questao->explicacao,
            'imagem_url' => $questao->imagem_url,
            'fonte' => $questao->fonteParaProps(),
            'historico_juridico' => $this->historicoJuridico($questao),
        ];
    }

    /** @return Builder */
    private function reportsAbertos()
    {
        return DB::table('reports_questao')->where('status', StatusDoReport::Aberto->value)->selectRaw('questao_id, count(*) as abertos')->groupBy('questao_id');
    }

    /** @return Builder */
    private function estatisticas()
    {
        $porAlternativa = DB::table('respostas as r')
            ->join('alternativas as a', 'a.id', '=', 'r.alternativa_id')
            ->selectRaw('r.questao_id, r.alternativa_id, a.correta, count(*) as n, sum(r.tempo_ms) as soma_ms')
            ->groupBy('r.questao_id', 'r.alternativa_id', 'a.correta');

        return DB::query()->fromSub($porAlternativa, 'pa')->selectRaw(
            'pa.questao_id, sum(pa.n) as total, coalesce(sum(pa.n) filter (where pa.correta), 0) as acertos, '
            .'coalesce(max(pa.n) filter (where not pa.correta), 0) as maior_distrator, sum(pa.soma_ms) as soma_ms'
        )->groupBy('pa.questao_id');
    }

    /** @param  array<string, mixed>  $filtros */
    private function consultaDaLista(array $filtros): Builder
    {
        $consulta = DB::table('questoes as q')
            ->leftJoin('assuntos as a', 'a.id', '=', 'q.assunto_id')
            ->leftJoin('materias as m', 'm.id', '=', 'a.materia_id')
            ->leftJoinSub($this->estatisticas(), 'e', 'e.questao_id', '=', 'q.id')
            ->leftJoinSub($this->reportsAbertos(), 'rp', 'rp.questao_id', '=', 'q.id')
            ->select('q.id', 'q.codigo', 'q.origem', 'q.revisao_juridica', 'q.assunto_id', 'q.area', 'a.nome as assunto', 'm.nome as materia')
            ->selectRaw('coalesce(e.total, 0) as total, coalesce(e.acertos, 0) as acertos, coalesce(e.soma_ms, 0) as soma_ms, coalesce(e.maior_distrator, 0) as maior_distrator, coalesce(rp.abertos, 0) as reports_abertos');

        return $this->aplicarFiltros($consulta, $filtros);
    }

    /** @param  array<string, mixed>  $filtros */
    private function aplicarFiltros(Builder $consulta, array $filtros): Builder
    {
        $juridico = (string) ($filtros['status_juridico'] ?? '');

        return $consulta
            ->when(in_array($filtros['origem'] ?? null, [Questao::ORIGEM_AUTORAL, Questao::ORIGEM_INEP], true), fn ($q) => $q->where('q.origem', $filtros['origem']))
            ->when(filled($filtros['materia'] ?? null), fn ($q) => $q->where('m.id', (int) $filtros['materia']))
            ->when(filled($filtros['assunto'] ?? null), fn ($q) => $q->where('q.assunto_id', (int) $filtros['assunto']))
            ->when(in_array($juridico, self::STATUS_JURIDICO, true), fn ($q) => $this->filtrarJuridico($q, $juridico))
            ->when(filter_var($filtros['suspeita'] ?? false, FILTER_VALIDATE_BOOLEAN), fn ($q) => $q->whereRaw($this->condicaoDeSuspeita(), $this->limitesDeSuspeita()));
    }

    private function filtrarJuridico(Builder $consulta, string $status): void
    {
        $consulta->where('q.origem', Questao::ORIGEM_INEP);

        $status === self::JURIDICO_PENDENTE
            ? $consulta->where(fn ($q) => $q->whereNull('q.revisao_juridica')->orWhere('q.revisao_juridica', self::JURIDICO_PENDENTE))
            : $consulta->where('q.revisao_juridica', $status);
    }

    /** @return literal-string */
    private function condicaoDeSuspeita(): string
    {
        return '((e.total >= ? and (e.acertos::float8 / e.total < ? or e.acertos::float8 / e.total > ? or e.maior_distrator > e.acertos)) or coalesce(rp.abertos, 0) >= ?)';
    }

    /** @return list<int|float> */
    private function limitesDeSuspeita(): array
    {
        $limites = config('gabaritu.admin.suspeita');

        return [$limites['min_respostas'], $limites['acerto_min'], $limites['acerto_max'], $limites['min_reports']];
    }

    private function suspeitasTotal(): int
    {
        return $this->consultaDaLista(['suspeita' => true])->count();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function distribuicao(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $linhas = DB::table('alternativas as a')
            ->leftJoin('respostas as r', fn ($j) => $j->on('r.alternativa_id', '=', 'a.id')->on('r.questao_id', '=', 'a.questao_id'))
            ->whereIn('a.questao_id', $ids)
            ->selectRaw('a.questao_id, a.letra, a.correta, count(r.id) as total')
            ->groupBy('a.questao_id', 'a.id', 'a.letra', 'a.correta')->orderBy('a.letra')->get();

        return $linhas->groupBy('questao_id')->map(function (Collection $alternativas): array {
            $soma = (int) $alternativas->sum('total');

            return $alternativas->map(fn ($a) => [
                'letra' => $a->letra,
                'correta' => (bool) $a->correta,
                'total' => (int) $a->total,
                'pct' => $soma === 0 ? null : round($a->total / $soma * 100, self::CASAS_DA_PORCENTAGEM),
            ])->all();
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $distribuicao
     * @return array<string, mixed>
     */
    private function linha(stdClass $q, array $distribuicao): array
    {
        $total = (int) $q->total;
        $acerto = $total === 0 ? null : round($q->acertos / $total * 100, self::CASAS_DA_PORCENTAGEM);
        $motivos = $this->motivosDeSuspeita($total, (int) $q->acertos, (int) $q->maior_distrator, (int) $q->reports_abertos);

        return [
            'id' => (int) $q->id,
            'codigo' => $q->codigo,
            'origem' => $q->origem,
            'materia' => $q->materia,
            'assunto' => $q->assunto,
            'area' => $q->area,
            'status_juridico' => $q->origem === Questao::ORIGEM_INEP ? ($q->revisao_juridica ?? self::JURIDICO_PENDENTE) : null,
            'respostas' => $total,
            'pct_acerto' => $acerto,
            'tempo_medio_ms' => $total === 0 ? null : (int) round($q->soma_ms / $total),
            'distribuicao' => $distribuicao,
            'reports_abertos' => (int) $q->reports_abertos,
            'suspeita' => $motivos !== [],
            'motivos_suspeita' => $motivos,
            'url' => route('admin.questoes.show', $q->id, absolute: false),
        ];
    }

    /** @return list<string> */
    private function motivosDeSuspeita(int $total, int $acertos, int $maiorDistrator, int $reportsAbertos): array
    {
        $limites = config('gabaritu.admin.suspeita');
        $amostraSuficiente = $total >= $limites['min_respostas'];

        return array_keys(array_filter([
            'acerto_baixo' => $amostraSuficiente && $acertos / $total < $limites['acerto_min'],
            'acerto_alto' => $amostraSuficiente && $acertos / $total > $limites['acerto_max'],
            'distrator_domina' => $amostraSuficiente && $maiorDistrator > $acertos,
            'reports' => $reportsAbertos >= $limites['min_reports'],
        ]));
    }

    /**
     * @param  Collection<int, stdClass>  $linhas
     * @return array{total: int, autorais: int, inep: int, respostas: int, pct_acerto: float|null}
     */
    private function somar(Collection $linhas): array
    {
        $respostas = (int) $linhas->sum('respostas');

        return [
            'total' => (int) $linhas->sum('total'),
            'autorais' => (int) $linhas->sum('autorais'),
            'inep' => (int) $linhas->sum('inep'),
            'respostas' => $respostas,
            'pct_acerto' => $respostas === 0 ? null : round($linhas->sum('acertos') / $respostas * 100, self::CASAS_DA_PORCENTAGEM),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function historicoJuridico(Questao $questao): array
    {
        return AdminAuditoria::query()->where('alvo_tipo', 'questao')->where('alvo_id', $questao->id)
            ->where('acao', AcaoDeAuditoria::StatusJuridicoAlterado->value)->orderByDesc('id')->limit(10)->get()
            ->map(fn (AdminAuditoria $a) => [
                'de' => $a->detalhes['de'] ?? null,
                'para' => $a->detalhes['para'] ?? null,
                'quando' => $a->criado_em->setTimezone(config('gabaritu.fuso'))->format('Y-m-d H:i'),
                'admin' => $a->admin_email,
            ])->all();
    }
}
