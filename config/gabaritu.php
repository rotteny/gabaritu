<?php

return [
    /*
    | Enquanto false, o app fica em modo "em breve": só a home e o robots.txt
    | existem; cadastro, login e todo o resto respondem 404.
    */
    'cadastro_aberto' => (bool) env('GABARITU_CADASTRO_ABERTO', false),

    'email_contato' => env('GABARITU_EMAIL_CONTATO', 'gabaritu@gorilash.com.br'),

    'repositorio_url' => env('GABARITU_REPOSITORIO_URL', 'https://github.com/rotteny/gabaritu'),

    'idade_minima' => 13,

    /* Prefixo secreto das rotas do painel admin (docs/admin.md). Sem barras nas pontas; vazio cai em "admin". */
    'admin_prefixo' => trim((string) env('GABARITU_ADMIN_PREFIXO', 'admin'), '/') ?: 'admin',

    'admin' => [
        'inatividade_minutos' => 30,
        'reconfirmacao_minutos' => 15,
        'login_tentativas_por_minuto' => 5,
        'codigos_recuperacao' => 8,
        'senha_minima' => 12,
        'por_pagina' => 25,
        /* Questão suspeita: amostra mínima, acerto fora da faixa ou distrator mais escolhido que a correta. */
        'suspeita' => ['min_respostas' => 20, 'acerto_min' => 0.15, 'acerto_max' => 0.95, 'min_reports' => 3],
        /* Tentativa de questionário sem finalizar há mais que isso conta como abandonada. */
        'abandono_horas' => 24,
        /* Pendências legais: autorização de responsável parada há mais que isso aparece na lista. */
        'autorizacao_parada_dias' => 7,
        /* Retenção: coortes semanais exibidas e marcos (dias depois do cadastro). */
        'retencao' => ['semanas' => 12, 'marcos' => [1, 7, 30]],
        'itens_por_lista' => 50,
        /* Saúde: diretório dos backups locais (vazio = não configurado) e versão do deploy (commit). */
        'backup_diretorio' => env('GABARITU_BACKUP_DIR'),
        'versao' => env('GABARITU_VERSAO'),
    ],

    /* Versão da página /privacidade gravada nos aceites. Mudou o texto, mude a versão. */
    'privacidade_versao' => '2026-11-25',

    /* Versão da página /termos gravada no aceite do responsável. Mudou o texto, mude a versão. */
    'termos_versao' => '2026-11-25',

    /* Avatares do aluno: lista fixa de bichos marinhos; não há upload de foto (não guardamos imagem de menor). */
    'avatar_padrao' => 'peixe-palhaco',

    'avatares' => [
        'peixe-palhaco', 'caranguejo', 'polvo', 'tartaruga', 'agua-viva', 'cavalo-marinho',
        'estrela-do-mar', 'golfinho', 'baleia', 'lula', 'ourico', 'lagosta',
    ],

    'fuso' => 'America/Sao_Paulo',

    /* Regras de nota, revisão e freio: docs/regras-nota.md */
    'nota' => [
        'dificuldade_b' => [1 => -1.0, 2 => 0.0, 3 => 1.0],
        'theta_inicial' => 0.0,
        'theta_min' => -2.0,
        'theta_max' => 4.0,
        'k_inicial' => 0.50,
        'k_normal' => 0.25,
        'k_inicial_ate' => 10,
        'peso_repetida' => 0.25,
        'min_respostas_nota' => 6,
        'amostra_boa' => 15,
        'nota_base' => 500,
        'nota_escala' => 100,
    ],

    'chute' => [
        'ms_min' => 3000,
        'ms_max' => 8000,
        'ms_por_caractere' => 20,
    ],

    'diagnostico' => [
        'janela' => 50,
        'min_erros' => 5,
        'min_ocorrencias' => 3,
        'max_exibidas' => 3,
    ],

    'revisao' => [
        'caixas_dias' => [1 => 1, 2 => 4, 3 => 14],
        'max_dia' => 10,
    ],

    'freio' => [
        'erros' => 3,
        'saida_acertos' => 2,
        'sessao_inativa_min' => 30,
    ],

    'falas' => [
        'recentes' => 8,
        'interface' => 'conteudo/falas-interface.json',
    ],

    'conteudo_path' => 'conteudo/materias',

    /* Questões de origem INEP (ENEM oficial), em análise jurídica: docs/imagens-questoes.md. Desligado, não aparecem para o aluno. */
    'exibir_inep' => (bool) env('GABARITU_EXIBIR_INEP', false),

    /* Imagens de questões e alternativas: docs/imagens-questoes.md. Caminho relativo a public/, nome pelo hash do conteúdo. */
    'imagens' => [
        'pasta' => public_path('conteudo/img'),
        'url_base' => '/conteudo/img',
        'max_svg_autoral_bytes' => 60 * 1024,
        'max_externa_bytes' => 400 * 1024,
        'max_pixels' => 16_000_000,
    ],
];
