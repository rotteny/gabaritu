<?php

namespace App\Enums;

enum MotivoDoReport: string
{
    case Enunciado = 'enunciado';
    case Imagem = 'imagem';
    case Gabarito = 'gabarito';
    case Explicacao = 'explicacao';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::Enunciado => 'Enunciado ou texto com problema',
            self::Imagem => 'Imagem com problema',
            self::Gabarito => 'O gabarito parece errado',
            self::Explicacao => 'A explicação está errada',
            self::Outro => 'Outro problema',
        };
    }

    /** @return list<array{valor: string, rotulo: string}> */
    public static function opcoes(): array
    {
        return array_map(fn (self $m) => ['valor' => $m->value, 'rotulo' => $m->rotulo()], self::cases());
    }
}
