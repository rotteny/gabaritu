<?php

use App\Models\Admin;
use App\Models\AdminAuditoria;

it('cria o admin com senha por prompt oculto e audita', function () {
    $this->artisan('gabaritu:admin', ['acao' => 'criar', 'email' => 'Dono@Gabaritu.test'])
        ->expectsQuestion('Senha (não aparece na tela)', 'Senha-Forte-12345')
        ->expectsQuestion('Repita a senha', 'Senha-Forte-12345')
        ->assertSuccessful();

    $admin = Admin::query()->where('email', 'dono@gabaritu.test')->sole();
    expect(Hash::check('Senha-Forte-12345', $admin->password))->toBeTrue()->and($admin->temDoisFatoresConfirmado())->toBeFalse();
    expect(AdminAuditoria::query()->where('acao', 'admin_criado')->count())->toBe(1);
});

it('não aceita senha fraca nem senhas diferentes e não tem argumento de senha', function () {
    $this->artisan('gabaritu:admin', ['acao' => 'criar', 'email' => 'dono@gabaritu.test'])
        ->expectsQuestion('Senha (não aparece na tela)', 'curta')->assertFailed();
    $this->artisan('gabaritu:admin', ['acao' => 'criar', 'email' => 'dono@gabaritu.test'])
        ->expectsQuestion('Senha (não aparece na tela)', 'Senha-Forte-12345')
        ->expectsQuestion('Repita a senha', 'Senha-Forte-54321')->assertFailed();
    expect(fn () => $this->artisan('gabaritu:admin', ['acao' => 'criar', 'email' => 'dono@gabaritu.test', 'senha' => 'x']))->toThrow(InvalidArgumentException::class);

    expect(Admin::count())->toBe(0);
});

it('troca a senha e reinicia o 2FA', function () {
    [$admin] = adminComDoisFatores();

    $this->artisan('gabaritu:admin', ['acao' => 'senha', 'email' => $admin->email])
        ->expectsQuestion('Senha (não aparece na tela)', 'Nova-Senha-Forte-99')
        ->expectsQuestion('Repita a senha', 'Nova-Senha-Forte-99')->assertSuccessful();
    expect(Hash::check('Nova-Senha-Forte-99', $admin->fresh()->password))->toBeTrue();

    $this->artisan('gabaritu:admin', ['acao' => '2fa-reset', 'email' => $admin->email])->assertSuccessful();
    $depois = $admin->fresh();
    expect($depois->temDoisFatoresConfirmado())->toBeFalse()->and($depois->dois_fatores_segredo)->toBeNull()->and($depois->codigos_recuperacao)->toBeNull();
});

it('falha para admin inexistente', function () {
    $this->artisan('gabaritu:admin', ['acao' => '2fa-reset', 'email' => 'nada@x.test'])->assertFailed();
});
