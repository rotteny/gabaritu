<?php

namespace App\Support;

class EmailMascarado
{
    private const int LETRAS_VISIVEIS = 2;

    public static function de(string $email): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $email, 2), 2, '');
        $visivel = mb_substr($usuario, 0, self::LETRAS_VISIVEIS);

        return $visivel.str_repeat('*', max(mb_strlen($usuario) - self::LETRAS_VISIVEIS, 3)).'@'.$dominio;
    }
}
