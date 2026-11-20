<?php

namespace App\Enums;

enum TipoConvite: string
{
    case Autorizacao = 'autorizacao';
    case Coresponsavel = 'coresponsavel';
    case CoresponsavelAluno = 'coresponsavel_aluno';
    case Transferencia = 'transferencia';

    /** Convites que acrescentam um responsável (a transferência só troca um por outro). */
    public function acrescentaResponsavel(): bool
    {
        return $this !== self::Transferencia;
    }
}
