<?php

use App\Models\User;

it('mantém o aluno logado nas props da página de erro 404', function () {
    $aluno = User::factory()->create();
    criarFala('erro_http:404', 'não achei');

    $this->post('/entrar', ['email' => $aluno->email, 'senha' => 'password'])->assertRedirect();

    $this->get('/nao-existe-mesmo')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Erro')
            ->where('auth.user.apelido', $aluno->apelido)
            ->has('gabaritu.email_contato'));
});
