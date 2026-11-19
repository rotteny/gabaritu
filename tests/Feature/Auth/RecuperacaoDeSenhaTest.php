<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('responde igual com e-mail existente ou não', function () {
    Notification::fake();
    User::factory()->create(['email' => 'existe@exemplo.test']);

    $this->post('/esqueci-senha', ['email' => 'existe@exemplo.test']);
    $statusExistente = session('status');
    $this->post('/esqueci-senha', ['email' => 'nao-existe@exemplo.test']);

    expect($statusExistente)->not->toBeNull()->and(session('status'))->toBe($statusExistente);
});

it('redefine a senha com o token', function () {
    Notification::fake();
    $usuario = User::factory()->create();
    $this->post('/esqueci-senha', ['email' => $usuario->email])->assertSessionHasNoErrors();

    Notification::assertSentTo($usuario, ResetPassword::class, function ($notificacao) use ($usuario) {
        $this->get('/redefinir-senha/'.$notificacao->token)
            ->assertOk()->assertInertia(fn ($page) => $page->component('auth/RedefinirSenha'));
        $this->post('/redefinir-senha', [
            'token' => $notificacao->token,
            'email' => $usuario->email,
            'senha' => 'nova-senha-123',
            'senha_confirmation' => 'nova-senha-123',
        ])->assertSessionHasNoErrors();

        return true;
    });
});
