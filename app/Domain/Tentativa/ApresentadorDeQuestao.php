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
            ->whereHas('questao', fn ($q) => $q->servivel())
            ->with('questao.alternativas', 'questao.assunto.materia')
            ->first();
    }

    public function temItemPendente(Tentativa $tentativa): bool
    {
        return $tentativa->itens()
            ->whereNotIn('questao_id', $tentativa->respostas()->select('questao_id'))
            ->whereHas('questao', fn ($q) => $q->servivel())
            ->exists();
    }

    public function marcarServida(TentativaQuestao $item): void
    {
        if ($item->servida_em === null) {
            $item->update(['servida_em' => now()]);
        }
    }

    /**
     * @return array{
     *     id: int, contexto: string|null, enunciado: string,
     *     imagem: array{url: string, alt: string, largura: int, altura: int}|null, fonte: string|null,
     *     alternativas: array<int, array{id: int, letra: string, texto: string, imagem: array{url: string, alt: string, largura: int, altura: int}|null}>
     * }
     */
    public function questao(Tentativa $tentativa, Questao $questao): array
    {
        $embaralhadas = $questao->alternativas
            ->sortBy(fn ($alternativa) => md5($tentativa->seed.'-'.$alternativa->id))
            ->values();

        return [
            'id' => $questao->id,
            'contexto' => $questao->contexto,
            'enunciado' => $questao->enunciado,
            'imagem' => $questao->imagemParaProps(),
            'fonte' => $questao->fonteParaProps(),
            'alternativas' => $embaralhadas->map(fn ($alternativa, int $posicao) => [
                'id' => $alternativa->id,
                'letra' => self::LETRAS[$posicao],
                'texto' => $alternativa->texto,
                'imagem' => $alternativa->imagemParaProps(),
            ])->values()->all(),
        ];
    }
}
