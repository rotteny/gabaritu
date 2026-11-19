<?php

namespace App\Domain\Diagnostico;

final readonly class ItemDiagnostico
{
    public function __construct(
        public int $etiquetaId,
        public int $ocorrencias,
        public int $percentual,
    ) {}
}
