<?php

namespace App\Domain\Freio;

final readonly class EstadoFreio
{
    public function __construct(
        public int $errosSeguidos = 0,
        public bool $ativo = false,
        public int $acertosNaSaida = 0,
        public ?int $ultimaRespostaEm = null,
    ) {}

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return get_object_vars($this);
    }

    /** @param  array<string, mixed>  $dados */
    public static function deArray(array $dados): self
    {
        return new self(
            (int) ($dados['errosSeguidos'] ?? 0),
            (bool) ($dados['ativo'] ?? false),
            (int) ($dados['acertosNaSaida'] ?? 0),
            isset($dados['ultimaRespostaEm']) ? (int) $dados['ultimaRespostaEm'] : null,
        );
    }
}
