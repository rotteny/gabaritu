<?php

namespace App\Domain\Admin;

/** Escapa o termo digitado para uso em LIKE: o que o admin digita é texto, nunca padrão. */
final class BuscaDeTexto
{
    public static function escapar(string $termo): string
    {
        return addcslashes(mb_strtolower(trim($termo)), '%_\\');
    }
}
