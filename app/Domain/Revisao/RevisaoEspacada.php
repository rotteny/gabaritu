<?php

namespace App\Domain\Revisao;

use Carbon\CarbonImmutable;

final class RevisaoEspacada
{
    private const int CAIXA_INICIAL = 1;

    public function decidir(?EstadoRevisao $atual, bool $correta, bool $chute, bool $emRevisao, CarbonImmutable $hoje): DecisaoRevisao
    {
        if (! $correta) {
            return $this->gravar(self::CAIXA_INICIAL, 0, $hoje);
        }

        if (! $emRevisao || $atual === null) {
            return new DecisaoRevisao(AcaoRevisao::Manter);
        }

        if ($chute) {
            return $this->gravar($atual->caixa, $atual->acertosSeguidos, $hoje, self::CAIXA_INICIAL);
        }

        $maxima = max(array_keys((array) config('gabaritu.revisao.caixas_dias')));

        return $atual->caixa >= $maxima
            ? new DecisaoRevisao(AcaoRevisao::Remover)
            : $this->gravar($atual->caixa + 1, $atual->acertosSeguidos + 1, $hoje);
    }

    private function gravar(int $caixa, int $acertosSeguidos, CarbonImmutable $hoje, ?int $caixaDosDias = null): DecisaoRevisao
    {
        $dias = (int) config('gabaritu.revisao.caixas_dias.'.($caixaDosDias ?? $caixa));

        return new DecisaoRevisao(
            AcaoRevisao::Gravar,
            new EstadoRevisao($caixa, $acertosSeguidos, $hoje->addDays($dias)),
        );
    }
}
