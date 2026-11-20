<?php

namespace App\Domain\Admin;

use Carbon\CarbonImmutable;

/** Janela dos gráficos: 30 ou 90 dias, ou tudo desde o primeiro cadastro. */
final readonly class Periodo
{
    private const array DIAS_VALIDOS = [30, 90];

    private const string TUDO = 'tudo';

    private function __construct(public ?int $dias) {}

    public static function de(mixed $valor): self
    {
        return new self(in_array((int) $valor, self::DIAS_VALIDOS, true) ? (int) $valor : ($valor === self::TUDO ? null : self::DIAS_VALIDOS[0]));
    }

    public function chave(): string
    {
        return $this->dias === null ? self::TUDO : (string) $this->dias;
    }

    /** Primeiro dia local da janela; sem limite, o do primeiro cadastro informado. */
    public function inicio(?CarbonImmutable $primeiroCadastro = null): CarbonImmutable
    {
        if ($this->dias !== null) {
            return Fuso::hoje()->subDays($this->dias - 1);
        }

        return ($primeiroCadastro ?? Fuso::hoje())->setTimezone(config('gabaritu.fuso'))->startOfDay();
    }
}
