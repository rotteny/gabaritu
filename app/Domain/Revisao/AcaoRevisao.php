<?php

namespace App\Domain\Revisao;

enum AcaoRevisao
{
    case Manter;
    case Gravar;
    case Remover;
}
