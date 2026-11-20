<?php

namespace App\Domain\Conteudo;

use App\Models\Questao;
use App\Models\Questionario;
use Illuminate\Support\Collection;

/** Questões INEP classificadas entram em questionários próprios: "ENEM <ano> · <n>", até 10 por assunto e por ano. */
class AgrupadorDeQuestionariosInep
{
    public const int POR_QUESTIONARIO = 10;

    /** Longe das ordens autorais (1..N) e dentro de unsignedSmallInteger: ano 2024, questionário 3 => 2403. */
    private const int BASE_DO_ANO = 2000;

    private const int FATOR_DO_ANO = 100;

    public function agrupar(): void
    {
        Questao::query()->where('origem', Questao::ORIGEM_INEP)->update(['questionario_id' => null]);

        $grupos = $this->elegiveis()->groupBy(fn (Questao $q) => $q->assunto_id.'|'.$q->origem_ref['ano']);

        foreach ($grupos as $questoes) {
            foreach ($questoes->chunk(self::POR_QUESTIONARIO)->values() as $indice => $lote) {
                $this->gravarLote($lote, $indice + 1);
            }
        }

        $this->removerVazios();
    }

    public function removerVazios(): void
    {
        Questionario::query()->where('tipo', Questionario::TIPO_INEP)->whereDoesntHave('questoes')->delete();
    }

    /** @return Collection<int, Questao> */
    private function elegiveis(): Collection
    {
        return Questao::query()
            ->where('origem', Questao::ORIGEM_INEP)
            ->whereNotNull('assunto_id')
            ->where('anulada', false)
            ->where('alt_pendente', false)
            ->where(fn ($q) => $q->whereNull('revisao_juridica')->orWhere('revisao_juridica', '!=', Questao::JURIDICO_REMOVER))
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'assunto_id', 'origem_ref']);
    }

    /** @param  Collection<int, Questao>  $lote */
    private function gravarLote(Collection $lote, int $numero): void
    {
        $primeira = $lote->first();
        $ano = (int) $primeira->origem_ref['ano'];

        $questionario = Questionario::updateOrCreate(
            ['assunto_id' => $primeira->assunto_id, 'ordem' => ($ano - self::BASE_DO_ANO) * self::FATOR_DO_ANO + $numero],
            ['titulo' => "ENEM {$ano} · {$numero}", 'tipo' => Questionario::TIPO_INEP],
        );

        Questao::query()->whereIn('id', $lote->pluck('id')->all())->update(['questionario_id' => $questionario->id]);
    }
}
