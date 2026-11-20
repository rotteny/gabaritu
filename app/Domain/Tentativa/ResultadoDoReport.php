<?php

namespace App\Domain\Tentativa;

enum ResultadoDoReport
{
    case Registrado;
    case JaReportou;
    case LimiteDiario;
    case NaoRespondeu;
}
