<?php

namespace App\Domain\Revisao;

final readonly class DecisaoRevisao
{
    public function __construct(
        public AcaoRevisao $acao,
        public ?EstadoRevisao $estado = null,
    ) {}
}
