<?php

namespace App\Domain\Conteudo;

use App\Models\Questao;
use App\Models\Questionario;
use Illuminate\Support\Collection;

/**
 * Questões INEP classificadas entram em questionários próprios por assunto, misturando os anos: "ENEM · <n>".
 * Ordem: ano decrescente, número e código (atribuição estável). Lotes de 10; sobra com menos de 5 vai para o lote anterior (até 14).
 */
class AgrupadorDeQuestionariosInep
{
    public const int POR_QUESTIONARIO = 10;

    public const int MINIMO_DO_ULTIMO_LOTE = 5;

    /** Longe das ordens autorais (1..N) e dentro de unsignedSmallInteger. */
    private const int BASE_DA_ORDEM = 2000;

    public function agrupar(): void
    {
        Questao::query()->where('origem', Questao::ORIGEM_INEP)->update(['questionario_id' => null]);

        foreach ($this->elegiveis()->groupBy('assunto_id') as $questoes) {
            foreach ($this->lotes($questoes) as $indice => $lote) {
                $this->gravarLote($lote, $indice + 1);
            }
        }

        $this->removerVazios();
    }

    /** Remove só o questionário INEP vazio e sem tentativas; com tentativas, fica no banco (vazio, logo escondido). */
    public function removerVazios(): void
    {
        Questionario::query()->where('tipo', Questionario::TIPO_INEP)
            ->whereDoesntHave('questoes')->whereDoesntHave('tentativas')->delete();
    }

    /**
     * @param  Collection<int, Questao>  $questoes
     * @return list<Collection<int, Questao>>
     */
    private function lotes(Collection $questoes): array
    {
        $lotes = $questoes->chunk(self::POR_QUESTIONARIO)->values()->all();

        if (count($lotes) > 1 && end($lotes)->count() < self::MINIMO_DO_ULTIMO_LOTE) {
            $sobra = array_pop($lotes);
            $anterior = array_pop($lotes);
            $lotes[] = $anterior->concat($sobra)->values();
        }

        return array_values($lotes);
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
            ->get(['id', 'codigo', 'assunto_id', 'origem_ref'])
            ->sortBy([
                fn (Questao $a, Questao $b) => $b->origem_ref['ano'] <=> $a->origem_ref['ano'],
                fn (Questao $a, Questao $b) => (int) $a->origem_ref['numero'] <=> (int) $b->origem_ref['numero'],
                fn (Questao $a, Questao $b) => $a->codigo <=> $b->codigo,
            ])->values();
    }

    /** @param  Collection<int, Questao>  $lote */
    private function gravarLote(Collection $lote, int $numero): void
    {
        $questionario = Questionario::updateOrCreate(
            ['assunto_id' => $lote->first()->assunto_id, 'ordem' => self::BASE_DA_ORDEM + $numero],
            ['titulo' => "ENEM · {$numero}", 'tipo' => Questionario::TIPO_INEP],
        );

        Questao::query()->whereIn('id', $lote->pluck('id')->all())->update(['questionario_id' => $questionario->id]);
    }
}
