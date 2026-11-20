<?php

namespace App\Domain\Admin;

use Illuminate\Support\Facades\DB;

/**
 * Falas do Peixoto vistas pelo admin. `feedbacks_fala.legal` é a curtida (true) ou a rejeição (false) do aluno.
 * Sem nenhum feedback ainda, a tela cai no fallback: falas mais exibidas (via `respostas.variacao_fala_id`).
 */
class EstatisticasDeFalas
{
    private const int TOPO = 10;

    /** @return array<string, mixed> */
    public function resumo(): array
    {
        $temFeedback = DB::table('feedbacks_fala')->exists();

        return [
            'tem_feedback' => $temFeedback,
            'mais_curtidas' => $temFeedback ? $this->ranking(true) : [],
            'mais_rejeitadas' => $temFeedback ? $this->ranking(false) : [],
            'por_nivel' => $temFeedback ? $this->agrupado('v.nivel') : [],
            'por_chave' => $temFeedback ? $this->agrupado('c.chave', self::TOPO) : [],
            'mais_exibidas' => $this->maisExibidas(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function ranking(bool $legal): array
    {
        return DB::table('feedbacks_fala as f')
            ->join('variacoes_fala as v', 'v.id', '=', 'f.variacao_fala_id')->join('chaves_fala as c', 'c.id', '=', 'v.chave_fala_id')
            ->where('f.legal', $legal)
            ->selectRaw('v.id, v.texto, v.nivel, c.chave, count(*) as votos')
            ->groupBy('v.id', 'v.texto', 'v.nivel', 'c.chave')->orderByDesc('votos')->limit(self::TOPO)->get()
            ->map(fn (object $l) => ['id' => (int) $l->id, 'texto' => $l->texto, 'nivel' => $l->nivel, 'chave' => $l->chave, 'votos' => (int) $l->votos])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function agrupado(string $coluna, ?int $limite = null): array
    {
        $consulta = DB::table('feedbacks_fala as f')
            ->join('variacoes_fala as v', 'v.id', '=', 'f.variacao_fala_id')->join('chaves_fala as c', 'c.id', '=', 'v.chave_fala_id')
            ->selectRaw("{$coluna} as grupo, count(*) filter (where f.legal) as curtidas, count(*) filter (where not f.legal) as rejeicoes") // @phpstan-ignore argument.type
            ->groupBy('grupo')->orderByRaw('count(*) desc');

        return $consulta->when($limite, fn ($q, int $n) => $q->limit($n))->get()
            ->map(fn (object $l) => ['grupo' => $l->grupo, 'curtidas' => (int) $l->curtidas, 'rejeicoes' => (int) $l->rejeicoes])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function maisExibidas(): array
    {
        return DB::table('respostas as r')
            ->join('variacoes_fala as v', 'v.id', '=', 'r.variacao_fala_id')->join('chaves_fala as c', 'c.id', '=', 'v.chave_fala_id')
            ->selectRaw('v.id, v.texto, v.nivel, c.chave, count(*) as exibicoes')
            ->groupBy('v.id', 'v.texto', 'v.nivel', 'c.chave')->orderByDesc('exibicoes')->limit(self::TOPO)->get()
            ->map(fn (object $l) => ['id' => (int) $l->id, 'texto' => $l->texto, 'nivel' => $l->nivel, 'chave' => $l->chave, 'exibicoes' => (int) $l->exibicoes])->all();
    }
}
