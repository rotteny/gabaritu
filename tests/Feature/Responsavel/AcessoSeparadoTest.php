<?php

use App\Domain\Responsavel\EmailDeResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

const SENHA_DE_TESTE = 'minha-senha-123';

beforeEach(function () {
    Mail::fake();
    $this->aluno = User::factory()->create(['email' => 'aluno@exemplo.test', 'password' => SENHA_DE_TESTE]);
    $this->responsavel = User::factory()->responsavel()->create(['email' => 'resp@exemplo.test', 'password' => SENHA_DE_TESTE]);
});

describe('login separado', function () {
    it('/entrar aceita só aluno; credencial de responsável cai na mensagem genérica', function () {
        $errado = $this->post('/entrar', ['email' => 'resp@exemplo.test', 'senha' => SENHA_DE_TESTE]);
        $inexistente = $this->post('/entrar', ['email' => 'ninguem@exemplo.test', 'senha' => SENHA_DE_TESTE]);

        $errado->assertSessionHasErrors('email');
        $this->assertGuest();
        expect(session('errors')->first('email'))->toBe('E-mail ou senha incorretos.');
        $inexistente->assertSessionHasErrors('email');
        $this->post('/entrar', ['email' => 'aluno@exemplo.test', 'senha' => SENHA_DE_TESTE])->assertRedirect('/inicio');
    });

    it('/responsavel/entrar aceita só responsável; credencial de aluno cai na mesma mensagem', function () {
        $this->post('/responsavel/entrar', ['email' => 'aluno@exemplo.test', 'senha' => SENHA_DE_TESTE])->assertSessionHasErrors('email');
        $this->assertGuest();
        expect(session('errors')->first('email'))->toBe('E-mail ou senha incorretos.');

        $this->post('/responsavel/entrar', ['email' => 'RESP@exemplo.test', 'senha' => 'errada'])->assertSessionHasErrors('email');
        $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => SENHA_DE_TESTE])->assertRedirect('/responsavel');
        $this->assertAuthenticatedAs($this->responsavel);
    });

    it('mostra a página de entrar do responsável', function () {
        $this->get('/responsavel/entrar')->assertOk()->assertInertia(fn ($page) => $page->component('responsavel/Entrar'));
        $this->get('/responsavel/esqueci-senha')->assertOk()->assertInertia(fn ($page) => $page->component('responsavel/EsqueciSenha'));
    });

    it('regenera a sessão no login e o logout volta para a landing', function () {
        $this->get('/responsavel/entrar');
        $antes = session()->getId();

        $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => SENHA_DE_TESTE]);
        expect(session()->getId())->not->toBe($antes);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    });

    it('limita tentativas de login do responsável', function () {
        foreach (range(1, 5) as $i) {
            $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => 'errada']);
        }

        $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => SENHA_DE_TESTE])->assertStatus(429);
    });

    it('quem já está logado é levado à própria área', function () {
        $this->actingAs($this->responsavel)->get('/responsavel/entrar')->assertRedirect('/responsavel');
        $this->actingAs($this->aluno)->get('/entrar')->assertRedirect('/inicio');
    });
});

describe('recuperação de senha separada', function () {
    it('o link do e-mail leva ao fluxo do tipo certo', function () {
        Notification::fake();
        $this->post('/responsavel/esqueci-senha', ['email' => 'resp@exemplo.test'])->assertSessionHasNoErrors();
        $this->post('/esqueci-senha', ['email' => 'aluno@exemplo.test']);

        $url = fn (User $u) => Notification::sent($u, ResetPassword::class)->first()->toMail($u)->actionUrl;
        expect($url($this->responsavel))->toContain('/responsavel/redefinir-senha/')
            ->and($url($this->aluno))->toContain('/redefinir-senha/')->not->toContain('/responsavel/');
    });

    it('/responsavel/esqueci-senha responde igual para e-mail existente, inexistente ou de aluno', function () {
        Notification::fake();

        $this->post('/responsavel/esqueci-senha', ['email' => 'resp@exemplo.test']);
        $existente = session('status');
        $this->post('/responsavel/esqueci-senha', ['email' => 'nao@exemplo.test']);
        $inexistente = session('status');
        $this->post('/responsavel/esqueci-senha', ['email' => 'aluno@exemplo.test']);

        expect($existente)->not->toBeNull()->and($inexistente)->toBe($existente)->and(session('status'))->toBe($existente);
        Notification::assertNotSentTo($this->aluno, ResetPassword::class);
    });

    it('redefine a senha do responsável pelo fluxo dele e derruba as sessões', function () {
        Notification::fake();
        $this->post('/responsavel/esqueci-senha', ['email' => 'resp@exemplo.test']);

        Notification::assertSentTo($this->responsavel, ResetPassword::class, function ($n) {
            $this->get('/responsavel/redefinir-senha/'.$n->token.'?email=resp@exemplo.test')->assertOk()
                ->assertInertia(fn ($page) => $page->component('responsavel/RedefinirSenha')->where('token', $n->token)->where('email', 'resp@exemplo.test'));
            $this->post('/responsavel/redefinir-senha', ['token' => $n->token, 'email' => 'resp@exemplo.test', 'senha' => 'nova-senha-456', 'senha_confirmation' => 'nova-senha-456'])
                ->assertRedirect('/responsavel/entrar')->assertSessionHasNoErrors();

            return true;
        });
        $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => 'nova-senha-456'])->assertRedirect('/responsavel');
    });

    it('o token de responsável não funciona no fluxo do aluno', function () {
        Notification::fake();
        $this->post('/responsavel/esqueci-senha', ['email' => 'resp@exemplo.test']);

        Notification::assertSentTo($this->responsavel, ResetPassword::class, function ($n) {
            $this->post('/redefinir-senha', ['token' => $n->token, 'email' => 'resp@exemplo.test', 'senha' => 'nova-senha-456', 'senha_confirmation' => 'nova-senha-456'])
                ->assertSessionHasErrors('email');

            return true;
        });
        $this->post('/responsavel/entrar', ['email' => 'resp@exemplo.test', 'senha' => SENHA_DE_TESTE])->assertRedirect('/responsavel');
    });

    it('o token de aluno não funciona no fluxo do responsável', function () {
        Notification::fake();
        $this->post('/esqueci-senha', ['email' => 'aluno@exemplo.test']);

        Notification::assertSentTo($this->aluno, ResetPassword::class, function ($n) {
            $this->post('/responsavel/redefinir-senha', ['token' => $n->token, 'email' => 'aluno@exemplo.test', 'senha' => 'nova-senha-456', 'senha_confirmation' => 'nova-senha-456'])
                ->assertSessionHasErrors('email');

            return true;
        });
    });
});

describe('uma conta por e-mail e tipos de conta', function () {
    it('o cadastro de aluno recusa e-mail de responsável, inclusive em outra caixa', function () {
        $this->post('/cadastro', dadosDeCadastro(['email' => 'RESP@exemplo.test']))->assertSessionHasErrors('email');

        expect(User::where('tipo', 'aluno')->count())->toBe(1);
    });

    it('o aceite não cria responsável com e-mail de conta existente', function () {
        [, $token] = conviteComToken(['email' => 'aluno@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertSessionHasErrors('email');
        expect(User::where('email', 'aluno@exemplo.test')->count())->toBe(1);
    });
});

describe('e-mail de responsável: nunca o do aluno nem o de conta de aluno', function () {
    function mensagemPadrao(): string
    {
        return EmailDeResponsavel::MENSAGEM;
    }

    it('normaliza maiúsculas, espaços e +tag do Gmail', function () {
        expect(EmailDeResponsavel::normalizar('  Foo.Bar+escola@Gmail.com '))->toBe('foo.bar@gmail.com')
            ->and(EmailDeResponsavel::normalizar('Foo+x@exemplo.test'))->toBe('foo+x@exemplo.test');
    });

    it('cadastro: barra o próprio e-mail (maiúsculas, +tag Gmail) e conta de aluno, com a mesma mensagem', function (string $email) {
        User::factory()->create(['email' => 'outro.aluno@exemplo.test']);

        $this->post('/cadastro', dadosDeCadastro([
            'email' => 'joao@gmail.com', 'ano_nascimento' => (int) now()->format('Y') - 16,
            'email_responsavel' => $email, 'parentesco_responsavel' => 'mae',
        ]))->assertSessionHasErrors(['email_responsavel' => mensagemPadrao()]);

        expect(User::where('email', 'joao@gmail.com')->count())->toBe(0);
    })->with(['JOAO@GMAIL.COM', 'joao+pai@gmail.com', 'Outro.Aluno@Exemplo.test']);

    it('correção do e-mail na espera', function (string $email) {
        $menor = User::factory()->aguardandoResponsavel()->create(['email' => 'menor@gmail.com']);

        $this->actingAs($menor)->patch('/aguardando-responsavel/email', ['email_responsavel' => $email])
            ->assertSessionHasErrors(['email_responsavel' => mensagemPadrao()]);
    })->with(['MENOR@gmail.com', 'menor+x@gmail.com', 'aluno@exemplo.test']);

    it('POST /conta/responsaveis retorna 422 para os dois casos', function (string $email) {
        $menor = User::factory()->comResponsavel()->create(['email' => 'menor@gmail.com']);

        $this->actingAs($menor)->postJson('/conta/responsaveis', ['email' => $email, 'parentesco' => 'pai'])
            ->assertStatus(422)->assertJsonValidationErrors(['email' => mensagemPadrao()]);
    })->with(['Menor+Tag@Gmail.com', 'ALUNO@exemplo.test']);

    it('convites de co-responsável e transferência retornam 422', function (string $tipo) {
        $aluno = User::factory()->create(['email' => 'menor@gmail.com']);
        VinculoPadrao::criar($aluno, $this->responsavel);

        foreach (['menor@gmail.com', 'ALUNO@exemplo.test'] as $email) {
            $this->actingAs($this->responsavel)->postJson("/responsavel/alunos/{$aluno->id}/convites", ['email' => $email, 'tipo' => $tipo, 'parentesco' => 'pai'])
                ->assertStatus(422)->assertJsonValidationErrors(['email' => mensagemPadrao()]);
        }
    })->with(['coresponsavel', 'transferencia']);

    it('o aceite por um aluno logado é barrado mesmo com 18 anos ou mais, com a mesma mensagem', function () {
        $adulto = User::factory()->comIdade(25)->create(['email' => 'dest@exemplo.test']);
        [, $token] = conviteComToken(['email' => 'dest@exemplo.test']);

        $this->actingAs($adulto)->postJson("/responsavel/convite/{$token}", dadosDeAceiteExistente())
            ->assertStatus(422)->assertJsonValidationErrors(['email' => mensagemPadrao()]);
        expect($adulto->fresh()->tipo)->toBe('aluno')->and(VinculoResponsavel::count())->toBe(0);
    });

    it('o aceite de visitante com e-mail de conta de aluno usa a mesma mensagem', function () {
        [, $token] = conviteComToken(['email' => 'aluno@exemplo.test']);

        $this->postJson("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertStatus(422)->assertJsonValidationErrors(['email' => mensagemPadrao()]);
    });
});

final class VinculoPadrao
{
    public static function criar(User $aluno, User $responsavel): void
    {
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id, 'responsavel_id' => $responsavel->id]);
    }
}
