<?php

return [
    /*
    | Enquanto false, o app fica em modo "em breve": só a home e o robots.txt
    | existem; cadastro, login e todo o resto respondem 404.
    */
    'cadastro_aberto' => (bool) env('GABARITU_CADASTRO_ABERTO', false),

    'email_contato' => env('GABARITU_EMAIL_CONTATO', 'gabaritu@gorilash.com.br'),

    'repositorio_url' => env('GABARITU_REPOSITORIO_URL', 'https://github.com/rotteny/gabaritu'),
];
