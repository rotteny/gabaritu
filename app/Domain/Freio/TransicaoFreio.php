<?php

namespace App\Domain\Freio;

final readonly class TransicaoFreio
{
    public function __construct(
        public EstadoFreio $estado,
        public ModoFreio $modo,
    ) {}
}
