<?php

namespace App\Domain\Conteudo;

use App\Domain\Conteudo\Imagens\ArmazemDeImagens;
use App\Models\Alternativa;
use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Apaga tudo o que veio do INEP: questões, alternativas, respostas, imagens e arquivos. Grava o que apagou no log de auditoria. */
class RemovedorDeInep
{
    public function __construct(
        private readonly ArmazemDeImagens $armazem,
        private readonly AgrupadorDeQuestionariosInep $agrupador,
    ) {}

    /** @return array{questoes: int, alternativas: int, respostas: int, imagens: int, arquivos: list<string>} */
    public function remover(string $executor): array
    {
        $ids = Questao::query()->where('origem', Questao::ORIGEM_INEP)->pluck('id')->map(fn ($id) => (int) $id)->values();
        $codigos = Questao::query()->whereIn('id', $ids)->pluck('codigo')->all();

        $resumo = DB::transaction(function () use ($ids): array {
            $contagem = [
                'respostas' => Resposta::query()->whereIn('questao_id', $ids)->count(),
                'alternativas' => Alternativa::query()->whereIn('questao_id', $ids)->count(),
                'imagens' => $this->contarImagens($ids->all()),
            ];
            Resposta::query()->whereIn('questao_id', $ids)->delete();
            $contagem['questoes'] = Questao::query()->whereIn('id', $ids)->delete();
            $this->agrupador->removerVazios();

            return $contagem;
        });

        $arquivos = $this->armazem->removerOrfas();
        $resumo += ['arquivos' => $arquivos];

        Log::channel('auditoria')->info('gabaritu:inep --remover', [
            'executor' => $executor,
            ...array_diff_key($resumo, ['arquivos' => 1]),
            'arquivos' => $arquivos,
            'questoes_codigos' => $codigos,
        ]);

        return $resumo;
    }

    /** @param  array<int, int>  $ids */
    private function contarImagens(array $ids): int
    {
        return Questao::query()->whereIn('id', $ids)->whereNotNull('imagem_url')->count()
            + Alternativa::query()->whereIn('questao_id', $ids)->whereNotNull('imagem_url')->count();
    }
}
