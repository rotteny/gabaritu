<?php

namespace App\Domain\Responsavel;

use App\Enums\Parentesco;

final readonly class DadosDeParentesco
{
    public function __construct(public Parentesco $parentesco, public ?string $outro = null) {}

    public static function de(string $parentesco, ?string $outro): self
    {
        $tipo = Parentesco::from($parentesco);

        return new self($tipo, $tipo === Parentesco::Outro ? trim((string) $outro) : null);
    }
}
