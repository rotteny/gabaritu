<?php

namespace App\Domain\Admin;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Dia do aluno é o de São Paulo; o banco guarda UTC. Tudo que agrupa ou compara por dia passa por aqui. */
final class Fuso
{
    public static function hoje(): CarbonImmutable
    {
        return CarbonImmutable::now(config('gabaritu.fuso'))->startOfDay();
    }

    /** Meia-noite local de `$diasAtras` dias atrás, já em UTC para comparar com as colunas. */
    public static function inicioDoDiaEmUtc(int $diasAtras = 0): CarbonImmutable
    {
        return self::hoje()->subDays($diasAtras)->setTimezone(config('app.timezone'));
    }

    public static function data(?CarbonInterface $momento): ?string
    {
        return $momento === null ? null : Carbon::instance($momento)->setTimezone(config('gabaritu.fuso'))->format('Y-m-d');
    }

    /** SQL do dia local de uma coluna timestamp (nome fixo no código, nunca vindo do usuário). */
    public static function diaSql(string $coluna): string
    {
        return sprintf(
            "(%s at time zone '%s' at time zone '%s')::date",
            $coluna,
            config('app.timezone'),
            config('gabaritu.fuso'),
        );
    }
}
