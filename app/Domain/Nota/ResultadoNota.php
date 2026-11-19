<?php

namespace App\Domain\Nota;

final readonly class ResultadoNota
{
    public function __construct(
        public float $theta,
        public int $nPrimeiras,
        public int $nota,
    ) {}
}
