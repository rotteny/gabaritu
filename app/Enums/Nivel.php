<?php

namespace App\Enums;

enum Nivel: string
{
    case Fofo = 'fofo';
    case Sarc = 'sarc';
    case Sem = 'sem';

    private const int IDADE_MINIMA_SEM_FILTRO = 15;

    public function ordem(): int
    {
        return match ($this) {
            self::Fofo => 1,
            self::Sarc => 2,
            self::Sem => 3,
        };
    }

    public static function tetoParaIdade(int $idade): self
    {
        return $idade >= self::IDADE_MINIMA_SEM_FILTRO ? self::Sem : self::Sarc;
    }

    public function limitadoPor(self $teto): self
    {
        return $this->ordem() <= $teto->ordem() ? $this : $teto;
    }

    /** @return list<string> */
    public function ateEste(): array
    {
        return array_values(array_map(
            fn (self $nivel) => $nivel->value,
            array_filter(self::cases(), fn (self $nivel) => $nivel->ordem() <= $this->ordem()),
        ));
    }
}
