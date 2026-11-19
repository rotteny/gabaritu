<?php

use App\Models\User;

function dadosDeCadastro(array $sobrescrever = []): array
{
    return [
        'apelido' => 'Peixotinho',
        'email' => 'aluno@exemplo.test',
        'senha' => 'senha-bem-longa-123',
        'senha_confirmation' => 'senha-bem-longa-123',
        'ano_nascimento' => (int) now()->format('Y') - 17,
        'serie' => '2em',
        'aceite_privacidade' => true,
        ...$sobrescrever,
    ];
}

it('mostra a página de cadastro do contrato', function () {
    $this->get('/cadastro')->assertOk()->assertInertia(fn ($page) => $page->component('auth/Cadastro'));
    $this->get('/entrar')->assertOk()->assertInertia(fn ($page) => $page->component('auth/Entrar'));
    $this->get('/esqueci-senha')->assertOk()->assertInertia(fn ($page) => $page->component('auth/EsqueciSenha'));
});

it('cria a conta, entra e manda para o início', function () {
    $this->post('/cadastro', dadosDeCadastro())->assertRedirect('/inicio');

    $this->assertAuthenticated();
    $usuario = User::firstWhere('email', 'aluno@exemplo.test');
    expect($usuario->apelido)->toBe('Peixotinho')->and($usuario->serie)->toBe('2em');
});

it('aceita exatamente 13 anos (idade = ano atual − ano de nascimento − 1)', function () {
    $anoLimite = (int) now()->format('Y') - 14;

    $this->post('/cadastro', dadosDeCadastro(['ano_nascimento' => $anoLimite]))->assertRedirect('/inicio');
});

it('recusa menor de 13 anos e não cria conta', function (int $anosAtras) {
    $ano = (int) now()->format('Y') - $anosAtras;

    $this->post('/cadastro', dadosDeCadastro(['ano_nascimento' => $ano]))
        ->assertSessionHasErrors('ano_nascimento');

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([13, 12, 5, 0]);

it('exige o aceite da privacidade', function () {
    $this->post('/cadastro', dadosDeCadastro(['aceite_privacidade' => false]))
        ->assertSessionHasErrors('aceite_privacidade');
    expect(User::count())->toBe(0);
});

it('exige série válida e senha confirmada', function () {
    $this->post('/cadastro', dadosDeCadastro(['serie' => '5ano', 'senha_confirmation' => 'outra']))
        ->assertSessionHasErrors(['serie', 'senha']);
});

it('começa no nível fofo', function () {
    $this->post('/cadastro', dadosDeCadastro());

    expect(User::firstOrFail()->zoeira)->toBe('fofo');
});

it('entra com senha e lembrar, e recusa senha errada com mensagem na chave email', function () {
    $usuario = User::factory()->create(['email' => 'a@b.test', 'password' => 'minha-senha-123']);

    $this->post('/entrar', ['email' => 'a@b.test', 'senha' => 'errada'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/entrar', ['email' => 'a@b.test', 'senha' => 'minha-senha-123', 'lembrar' => true])
        ->assertRedirect('/inicio');
    $this->assertAuthenticatedAs($usuario);
});

it('sai da conta', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
