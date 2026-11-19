<?php

namespace App\Domain\Nota;

final class NotaCalculator
{
    private const int ARREDONDAR_DE = 10;

    public function atualizar(float $theta, int $dificuldade, bool $correta, int $nPrimeiras, bool $repetida): ResultadoNota
    {
        $b = (float) config("gabaritu.nota.dificuldade_b.{$dificuldade}");
        $esperado = 1 / (1 + exp(-($theta - $b)));
        $passo = $this->passo($nPrimeiras) * ($repetida ? (float) config('gabaritu.nota.peso_repetida') : 1.0);
        $novo = $theta + $passo * (($correta ? 1 : 0) - $esperado);
        $novo = max((float) config('gabaritu.nota.theta_min'), min((float) config('gabaritu.nota.theta_max'), $novo));
        $novoN = $repetida ? $nPrimeiras : $nPrimeiras + 1;

        return new ResultadoNota($novo, $novoN, $this->notaInterna($novo));
    }

    public function notaInterna(float $theta): int
    {
        return (int) round((float) config('gabaritu.nota.nota_base') + (float) config('gabaritu.nota.nota_escala') * $theta);
    }

    public function exibida(float $theta, int $nPrimeiras): ?int
    {
        if ($nPrimeiras < (int) config('gabaritu.nota.min_respostas_nota')) {
            return null;
        }

        return (int) (round($this->notaInterna($theta) / self::ARREDONDAR_DE) * self::ARREDONDAR_DE);
    }

    public function amostraBaixa(int $nPrimeiras): bool
    {
        return $nPrimeiras < (int) config('gabaritu.nota.amostra_boa');
    }

    private function passo(int $nPrimeiras): float
    {
        return $nPrimeiras < (int) config('gabaritu.nota.k_inicial_ate')
            ? (float) config('gabaritu.nota.k_inicial')
            : (float) config('gabaritu.nota.k_normal');
    }
}
