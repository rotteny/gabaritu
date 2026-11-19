<?php

namespace App\Domain\Revisao;

use Carbon\CarbonImmutable;

final readonly class EstadoRevisao
{
    public function __construct(
        public int $caixa,
        public int $acertosSeguidos,
        public CarbonImmutable $proximaEm,
    ) {}
}
