<?php

namespace App\Domain\Freio;

final class Freio
{
    public function registrar(EstadoFreio $estado, bool $correta, int $agora): TransicaoFreio
    {
        $estado = $this->descartarSeInativo($estado, $agora);

        return $estado->ativo
            ? $this->noModoAjuda($estado, $correta, $agora)
            : $this->noModoNormal($estado, $correta, $agora);
    }

    private function noModoNormal(EstadoFreio $estado, bool $correta, int $agora): TransicaoFreio
    {
        $erros = $correta ? 0 : $estado->errosSeguidos + 1;

        if ($erros >= (int) config('gabaritu.freio.erros')) {
            return new TransicaoFreio(new EstadoFreio(0, true, 0, $agora), ModoFreio::Ajuda);
        }

        return new TransicaoFreio(new EstadoFreio($erros, false, 0, $agora), ModoFreio::Normal);
    }

    private function noModoAjuda(EstadoFreio $estado, bool $correta, int $agora): TransicaoFreio
    {
        $acertos = $correta ? $estado->acertosNaSaida + 1 : 0;

        if ($acertos >= (int) config('gabaritu.freio.saida_acertos')) {
            return new TransicaoFreio(new EstadoFreio(0, false, 0, $agora), ModoFreio::Saida);
        }

        return new TransicaoFreio(new EstadoFreio(0, true, $acertos, $agora), ModoFreio::Ajuda);
    }

    private function descartarSeInativo(EstadoFreio $estado, int $agora): EstadoFreio
    {
        $limite = (int) config('gabaritu.freio.sessao_inativa_min') * 60;

        return $estado->ultimaRespostaEm !== null && $agora - $estado->ultimaRespostaEm > $limite
            ? new EstadoFreio
            : $estado;
    }
}
