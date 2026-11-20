<?php

namespace App\Enums;

enum Parentesco: string
{
    case Pai = 'pai';
    case Mae = 'mae';
    case Avo = 'avo';
    case Tutor = 'tutor';
    case Outro = 'outro';
}
