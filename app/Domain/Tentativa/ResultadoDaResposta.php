<?php

namespace App\Domain\Tentativa;

use App\Domain\Falas\Fala;
use App\Models\Alternativa;

final readonly class ResultadoDaResposta
{
    public function __construct(
        public bool $correta,
        public Alternativa $alternativaCorreta,
        public ?Alternativa $alternativaEscolhida,
        public string $explicacao,
        public Fala $fala,
        public bool $freioAtivo,
        public bool $chute,
        public bool $tentativaTerminou,
    ) {}
}
