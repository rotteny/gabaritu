<?php

namespace App\Domain\Conteudo;

use RuntimeException;

class ImportacaoRecusada extends RuntimeException
{
    /** @param  list<string>  $erros */
    public function __construct(public readonly array $erros)
    {
        parent::__construct('Importação recusada: '.implode(' | ', $erros));
    }
}
