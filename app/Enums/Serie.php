<?php

namespace App\Enums;

enum Serie: string
{
    case NonoAno = '9ano';
    case PrimeiroEm = '1em';
    case SegundoEm = '2em';
    case TerceiroEm = '3em';
    case Cursinho = 'cursinho';

    public function rotulo(): string
    {
        return match ($this) {
            self::NonoAno => '9º ano',
            self::PrimeiroEm => '1º ano do ensino médio',
            self::SegundoEm => '2º ano do ensino médio',
            self::TerceiroEm => '3º ano do ensino médio',
            self::Cursinho => 'cursinho',
        };
    }
}
