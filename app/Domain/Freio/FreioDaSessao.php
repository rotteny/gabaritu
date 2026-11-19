<?php

namespace App\Domain\Freio;

use Illuminate\Contracts\Session\Session;

class FreioDaSessao
{
    private const string CHAVE = 'freio';

    public function __construct(private readonly Freio $freio) {}

    public function registrar(Session $sessao, bool $correta): TransicaoFreio
    {
        $atual = EstadoFreio::deArray((array) $sessao->get(self::CHAVE, []));
        $transicao = $this->freio->registrar($atual, $correta, now()->getTimestamp());
        $sessao->put(self::CHAVE, $transicao->estado->paraArray());

        return $transicao;
    }
}
