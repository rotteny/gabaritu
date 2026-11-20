<?php

use App\Models\User;
use Database\Seeders\ResponsavelDePreviaSeeder;

it('o seeder local cria um responsável com 2 alunos ativos', function () {
    $this->seed(ResponsavelDePreviaSeeder::class);

    $responsavel = User::firstWhere('email', 'responsavel@gabaritu.test');
    expect($responsavel->tipo)->toBe('responsavel')->and($responsavel->alunos()->count())->toBe(2)
        ->and($responsavel->alunos()->where('users.status', 'ativo')->count())->toBe(2);
    $this->post('/responsavel/entrar', ['email' => 'responsavel@gabaritu.test', 'senha' => 'senha-previa-123'])->assertRedirect('/responsavel');
});
