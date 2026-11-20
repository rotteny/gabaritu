<?php

namespace App\Domain\Admin;

enum AcaoDeAuditoria: string
{
    case Login = 'login';
    case DoisFatoresConfirmado = 'dois_fatores_confirmado';
    case CodigoDeRecuperacaoUsado = 'codigo_de_recuperacao_usado';
    case AbrirAluno = 'abrir_aluno';
    case AbrirResponsavel = 'abrir_responsavel';
    case RevelarEmail = 'revelar_email';
    case StatusJuridicoAlterado = 'status_juridico_alterado';
    case AdminCriado = 'admin_criado';
    case SenhaTrocada = 'senha_trocada';
    case DoisFatoresResetado = 'dois_fatores_resetado';
}
