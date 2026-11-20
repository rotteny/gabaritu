<?php

use App\Mail\CadastroConfirmado;
use App\Mail\ConviteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function cadastroDeMenor(array $sobrescrever = []): array
{
    return dadosDeCadastro([
        'ano_nascimento' => (int) now()->format('Y') - 16,
        'email_responsavel' => 'Mae@Exemplo.test',
        'parentesco_responsavel' => 'mae',
        ...$sobrescrever,
    ]);
}

beforeEach(fn () => Mail::fake());

it('cadastra o menor aguardando responsável, manda para a espera e envia o convite', function () {
    $this->post('/cadastro', cadastroDeMenor())->assertRedirect('/aguardando-responsavel');

    $aluno = User::firstWhere('email', 'aluno@exemplo.test');
    expect($aluno->status)->toBe('aguardando_responsavel')
        ->and($aluno->tipo)->toBe('aluno')
        ->and($aluno->email_responsavel)->toBe('mae@exemplo.test')
        ->and($aluno->pendente_desde)->not->toBeNull();

    $convite = ConviteResponsavel::firstOrFail();
    expect($convite->tipo)->toBe('autorizacao')->and($convite->email)->toBe('mae@exemplo.test')
        ->and($convite->expira_em->isSameDay(now()->addDays(7)))->toBeTrue();
    Mail::assertSent(ConviteDeResponsavel::class, fn ($m) => $m->hasTo('mae@exemplo.test'));
    Mail::assertSent(CadastroConfirmado::class, fn ($m) => $m->hasTo('aluno@exemplo.test') && $m->convite?->id === $convite->id);
});

it('guarda só o hash do token, que não é o token do e-mail', function () {
    $this->post('/cadastro', cadastroDeMenor());

    Mail::assertSent(ConviteDeResponsavel::class, function (ConviteDeResponsavel $mail) {
        $convite = ConviteResponsavel::firstOrFail();

        return $convite->token_hash === hash('sha256', $mail->token) && $convite->token_hash !== $mail->token;
    });
});

it('mantém o cadastro de adulto igual: ativo, sem responsável e sem convite', function () {
    $this->post('/cadastro', dadosDeCadastro(['email_responsavel' => 'ignorado@exemplo.test']))->assertRedirect('/inicio');

    $aluno = User::firstOrFail();
    expect($aluno->status)->toBe('ativo')->and($aluno->email_responsavel)->toBeNull();
    expect(ConviteResponsavel::count())->toBe(0);
    Mail::assertNotSent(ConviteDeResponsavel::class);
    Mail::assertSent(CadastroConfirmado::class, fn ($m) => $m->convite === null);
});

it('trata 18 anos como adulto e 17 como menor', function () {
    $ano = (int) now()->format('Y');

    $this->post('/cadastro', dadosDeCadastro(['ano_nascimento' => $ano - 19]))->assertRedirect('/inicio');
    $this->post('/logout');
    $this->post('/cadastro', dadosDeCadastro(['email' => 'b@exemplo.test', 'ano_nascimento' => $ano - 18, 'email_responsavel' => 'x@exemplo.test', 'parentesco_responsavel' => 'pai']))
        ->assertRedirect('/aguardando-responsavel');
});

it('exige e-mail e parentesco do responsável para menor, e que o e-mail seja diferente do aluno', function () {
    $this->post('/cadastro', cadastroDeMenor(['email_responsavel' => null, 'parentesco_responsavel' => null]))
        ->assertSessionHasErrors(['email_responsavel', 'parentesco_responsavel']);
    $this->post('/cadastro', cadastroDeMenor(['email_responsavel' => 'ALUNO@exemplo.test']))
        ->assertSessionHasErrors('email_responsavel');
    $this->post('/cadastro', cadastroDeMenor(['parentesco_responsavel' => 'outro']))
        ->assertSessionHasErrors('parentesco_outro');

    expect(User::count())->toBe(0);
});

it('aceita parentesco outro com texto livre', function () {
    $this->post('/cadastro', cadastroDeMenor(['parentesco_responsavel' => 'outro', 'parentesco_outro' => 'tio']))
        ->assertRedirect('/aguardando-responsavel');

    expect(ConviteResponsavel::firstOrFail()->parentesco_outro)->toBe('tio');
});

it('aceita o e-mail de um responsável que já tem conta, sem avisar o aluno', function () {
    User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);

    $this->post('/cadastro', cadastroDeMenor())->assertRedirect('/aguardando-responsavel')->assertSessionHasNoErrors();
});

it('leva responsável para o painel e aluno pendente para a espera no login', function () {
    User::factory()->responsavel()->create(['email' => 'r@exemplo.test', 'password' => 'minha-senha-123']);
    User::factory()->aguardandoResponsavel()->create(['email' => 'p@exemplo.test', 'password' => 'minha-senha-123']);
    User::factory()->create(['email' => 'a@exemplo.test', 'password' => 'minha-senha-123']);

    $this->post('/responsavel/entrar', ['email' => 'r@exemplo.test', 'senha' => 'minha-senha-123'])->assertRedirect('/responsavel');
    $this->post('/logout')->assertRedirect('/');
    foreach (['p@exemplo.test' => '/aguardando-responsavel', 'a@exemplo.test' => '/inicio'] as $email => $destino) {
        $this->post('/entrar', ['email' => $email, 'senha' => 'minha-senha-123'])->assertRedirect($destino);
        $this->post('/logout');
    }
});

it('honra ?redirect= só para caminho interno', function (string $redirect, bool $vale) {
    User::factory()->responsavel()->create(['email' => 'r@exemplo.test', 'password' => 'minha-senha-123']);

    $this->get('/responsavel/entrar?redirect='.urlencode($redirect))->assertOk();
    $resposta = $this->post('/responsavel/entrar', ['email' => 'r@exemplo.test', 'senha' => 'minha-senha-123']);

    $vale ? $resposta->assertRedirect(url($redirect)) : $resposta->assertRedirect('/responsavel');
})->with([
    'interno' => ['/responsavel/convite/abc', true],
    'url externa' => ['https://evil.test/x', false],
    'protocolo relativo' => ['//evil.test/x', false],
    'barra invertida' => ['/\\evil.test', false],
]);
