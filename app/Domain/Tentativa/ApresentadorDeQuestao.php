<?php

namespace App\Domain\Tentativa;

use App\Models\Questao;
use App\Models\Tentativa;
use App\Models\TentativaQuestao;

class ApresentadorDeQuestao
{
    private const string LETRAS = 'ABCDE';

    public function itemAtual(Tentativa $tentativa): ?TentativaQuestao
    {
        $respondidas = $tentativa->respostas()->pluck('questao_id')->all();

        return $tentativa->itens()
            ->whereNotIn('questao_id', $respondidas)
            ->whereHas('questao', fn ($q) => $q->where('status', Questao::STATUS_ATIVA))
            ->with('questao.alternativas', 'questao.assunto.materia')
            ->first();
    }

    public function marcarServida(TentativaQuestao $item): void
    {
        if ($item->servida_em === null) {
            $item->update(['servida_em' => now()]);
        }
    }

    /** @return array{id: int, contexto: string|null, enunciado: string, alternativas: list<array{id: int, letra: string, texto: string}>} */
    public function questao(Tentativa $tentativa, Questao $questao): array
    {
        $embaralhadas = $questao->alternativas
            ->sortBy(fn ($alternativa) => md5($tentativa->seed.'-'.$alternativa->id))
            ->values();

        return [
            'id' => $questao->id,
            'contexto' => $questao->contexto,
            'enunciado' => $questao->enunciado,
            'alternativas' => $embaralhadas->map(fn ($alternativa, int $posicao) => [
                'id' => $alternativa->id,
                'letra' => self::LETRAS[$posicao],
                'texto' => $alternativa->texto,
            ])->all(),
        ];
    }
}
