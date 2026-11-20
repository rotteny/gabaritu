<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function sessaoGravada(User $aluno): string
{
    $id = Str::random(40);
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $aluno->id, 'payload' => '', 'last_activity' => now()->timestamp]);

    return $id;
}

it('grava quando e qual versão da política o aluno aceitou', function () {
    $this->post('/cadastro', [
        'apelido' => 'Peixotinho', 'email' => 'aluno@exemplo.test', 'senha' => 'senha-bem-longa-123',
        'senha_confirmation' => 'senha-bem-longa-123', 'ano_nascimento' => (int) now()->format('Y') - 30,
        'serie' => '2em', 'aceite_privacidade' => true,
    ])->assertRedirect('/inicio');

    $aluno = User::firstWhere('email', 'aluno@exemplo.test');
    expect($aluno->privacidade_aceita_em)->not->toBeNull()
        ->and($aluno->privacidade_versao)->toBe(config('gabaritu.privacidade_versao'));
});

it('não deixa o perfil forjar o aceite nem a idade', function () {
    $aluno = User::factory()->create(['ano_nascimento' => (int) now()->format('Y') - 14]);

    $this->actingAs($aluno)->patch('/conta/perfil', [
        'apelido' => 'Novo', 'serie' => '1em', 'ano_nascimento' => 1990, 'privacidade_versao' => 'x', 'email' => 'outro@exemplo.test',
    ])->assertSessionHasNoErrors();

    $aluno->refresh();
    expect($aluno->ano_nascimento)->toBe((int) now()->format('Y') - 14)
        ->and($aluno->privacidade_versao)->toBeNull()
        ->and($aluno->email)->not->toBe('outro@exemplo.test');
});

it('limita o cadastro por IP', function () {
    foreach (range(1, 10) as $n) {
        $this->post('/cadastro', ['email' => "robo{$n}@exemplo.test"])->assertStatus(302);
    }

    $this->post('/cadastro', ['email' => 'robo11@exemplo.test'])->assertStatus(429);
});

it('limita a recuperação de senha por e-mail', function () {
    Notification::fake();

    foreach (range(1, 5) as $n) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$n}"])->post('/esqueci-senha', ['email' => 'alvo@exemplo.test']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post('/esqueci-senha', ['email' => 'alvo@exemplo.test'])->assertStatus(429);
});

it('não revela o e-mail cadastrado quando o pedido repete dentro do intervalo do broker', function () {
    Notification::fake();
    User::factory()->create(['email' => 'existe@exemplo.test']);

    $this->post('/esqueci-senha', ['email' => 'existe@exemplo.test']);
    $this->post('/esqueci-senha', ['email' => 'existe@exemplo.test'])->assertSessionHasNoErrors();
    $statusExistente = session('status');
    $this->post('/esqueci-senha', ['email' => 'nao-existe@exemplo.test'])->assertSessionHasNoErrors();

    expect($statusExistente)->toBe(session('status'));
});

it('derruba as outras sessões ao trocar a senha', function () {
    $aluno = User::factory()->create(['remember_token' => 'antigo']);
    $outra = sessaoGravada($aluno);

    $this->actingAs($aluno)->put('/conta/senha', [
        'senha_atual' => 'password', 'senha' => 'nova-senha-123', 'senha_confirmation' => 'nova-senha-123',
    ])->assertSessionHasNoErrors();

    expect(DB::table('sessions')->where('id', $outra)->exists())->toBeFalse()
        ->and($aluno->refresh()->remember_token)->not->toBe('antigo');
});

it('derruba todas as sessões ao redefinir a senha pelo e-mail', function () {
    Notification::fake();
    $aluno = User::factory()->create(['remember_token' => 'antigo']);
    $sessao = sessaoGravada($aluno);
    $this->post('/esqueci-senha', ['email' => $aluno->email]);

    Notification::assertSentTo($aluno, ResetPassword::class, function ($notificacao) use ($aluno) {
        $this->post('/redefinir-senha', [
            'token' => $notificacao->token, 'email' => $aluno->email,
            'senha' => 'nova-senha-123', 'senha_confirmation' => 'nova-senha-123',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect(DB::table('sessions')->where('id', $sessao)->exists())->toBeFalse()
        ->and($aluno->refresh()->remember_token)->not->toBe('antigo');
});
