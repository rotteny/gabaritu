<?php

namespace App\Enums;

enum StatusDoReport: string
{
    case Aberto = 'aberto';
    case Resolvido = 'resolvido';
    case Descartado = 'descartado';
}
