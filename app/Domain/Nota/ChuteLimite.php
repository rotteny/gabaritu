<?php

namespace App\Domain\Nota;

final class ChuteLimite
{
    /** @param  iterable<string>  $textosAlternativas */
    public static function calcular(string $enunciado, iterable $textosAlternativas): int
    {
        $caracteres = mb_strlen($enunciado);
        foreach ($textosAlternativas as $texto) {
            $caracteres += mb_strlen($texto);
        }

        $bruto = $caracteres * (int) config('gabaritu.chute.ms_por_caractere');

        return max((int) config('gabaritu.chute.ms_min'), min((int) config('gabaritu.chute.ms_max'), $bruto));
    }

    public static function ehChute(int $tempoMs, int $limiteMs): bool
    {
        return $tempoMs < $limiteMs;
    }
}
