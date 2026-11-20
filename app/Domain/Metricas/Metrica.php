<?php

namespace App\Domain\Metricas;

/** Colunas de metricas_diarias que o sistema incrementa. O valor é o nome da coluna (lista fechada, nunca vem de fora). */
enum Metrica: string
{
    case AlunosAtivos = 'alunos_ativos';
    case Logins = 'logins';
    case Respostas = 'respostas';
    case VisitasLanding = 'visitas_landing';
}
