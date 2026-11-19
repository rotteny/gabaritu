<?php

namespace App\Domain\Falas;

use JsonSerializable;

final readonly class Fala implements JsonSerializable
{
    public const string HUMOR_FELIZ = 'feliz';

    public const string HUMOR_BRAVO = 'bravo';

    public const string HUMOR_NEUTRO = 'neutro';

    public const string HUMOR_AJUDA = 'ajuda';

    private const string TEXTO_RESERVA_FINAL = 'Peixoto sem fala no momento.';

    public function __construct(
        public string $texto,
        public string $humor,
        public ?int $variacaoId,
    ) {}

    public static function vazia(string $humor): self
    {
        return new self(self::TEXTO_RESERVA_FINAL, $humor, null);
    }

    /** @return array{texto: string, humor: string, variacao_id: int|null} */
    public function jsonSerialize(): array
    {
        return ['texto' => $this->texto, 'humor' => $this->humor, 'variacao_id' => $this->variacaoId];
    }
}
