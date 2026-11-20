<?php

use App\Mail\AlunoAutorizado;
use App\Mail\AutorizacaoConfirmada;
use App\Mail\AutorizacaoRecusada;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

describe('página do convite', function () {
    it('mostra o convite válido com o token', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['apelido' => 'Joaozinho', 'serie' => '1em']);
        [$convite, $token] = conviteComToken(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->get("/responsavel/convite/{$token}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('responsavel/Convite')
            ->where('token', $token)
            ->where('vencido', false)
            ->where('convite.aluno', ['apelido' => 'Joaozinho', 'serie' => '1em'])
            ->where('convite.email', 'mae@exemplo.test')
            ->where('convite.tipo', 'autorizacao')
            ->where('convite.parentesco', 'mae')
            ->where('tem_conta', false)
            ->where('logado_como_destinatario', false));
    });

    it('indica conta existente e destinatário logado', function () {
        $responsavel = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        [, $token] = conviteComToken(['email' => 'mae@exemplo.test']);

        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('tem_conta', true)->where('logado_como_destinatario', false));
        $this->actingAs($responsavel)->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('logado_como_destinatario', true));
    });

    it('token inexistente: convite nulo, não vencido', function () {
        $this->get('/responsavel/convite/nao-existe')->assertOk()->assertInertia(fn ($page) => $page
            ->component('responsavel/Convite')->where('convite', null)->where('vencido', false)->where('token', 'nao-existe'));
    });

    it('token vencido: convite nulo e vencido', function () {
        [, $token] = conviteComToken(['expira_em' => now()->subDay()]);

        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('convite', null)->where('vencido', true));
    });
});

describe('responsável novo', function () {
    it('cria a conta, vincula, libera o aluno e grava o aceite', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['email' => 'aluno@exemplo.test']);
        [$convite, $token] = conviteComToken(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['parentesco' => 'avo']))
            ->assertRedirect('/responsavel');

        $responsavel = User::firstWhere('email', 'mae@exemplo.test');
        expect($responsavel->tipo)->toBe('responsavel')->and($responsavel->apelido)->toBe('Maria da Silva');
        $this->assertAuthenticatedAs($responsavel);

        $vinculo = VinculoResponsavel::firstOrFail();
        expect($vinculo->aluno_id)->toBe($aluno->id)->and($vinculo->parentesco)->toBe('avo')
            ->and($vinculo->aceite_ip)->toBe('203.0.113.9')
            ->and($vinculo->termos_versao)->toBe(config('gabaritu.termos_versao'))
            ->and($vinculo->privacidade_versao)->toBe(config('gabaritu.privacidade_versao'))
            ->and($vinculo->declara_responsavel)->toBeTrue()->and($vinculo->aceite_termos)->toBeTrue()
            ->and($vinculo->aceito_em->isToday())->toBeTrue();
        expect($aluno->fresh()->status)->toBe('ativo')->and($aluno->fresh()->pendente_desde)->toBeNull();
        expect($convite->fresh()->usado_em)->not->toBeNull();
        Mail::assertSent(AlunoAutorizado::class, fn ($m) => $m->hasTo('aluno@exemplo.test'));
        Mail::assertSent(AutorizacaoConfirmada::class, fn ($m) => $m->hasTo('mae@exemplo.test') && $m->vinculo->id === $vinculo->id);
    });

    it('exige as duas declarações, senha confirmada e 18 anos', function () {
        [, $token] = conviteComToken();

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['declara_responsavel' => false, 'aceite_termos' => false, 'senha_confirmation' => 'x', 'ano_nascimento' => (int) now()->format('Y') - 17]))
            ->assertSessionHasErrors(['declara_responsavel', 'aceite_termos', 'senha', 'ano_nascimento']);
        expect(VinculoResponsavel::count())->toBe(0)->and(User::where('tipo', 'responsavel')->count())->toBe(0);
    });

    it('não aceita o e-mail de uma conta de aluno', function () {
        User::factory()->create(['email' => 'mae@exemplo.test']);
        [, $token] = conviteComToken(['email' => 'mae@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertSessionHasErrors('email');
        expect(VinculoResponsavel::count())->toBe(0);
    });
});

describe('responsável existente', function () {
    it('visitante com conta é mandado para entrar e voltar ao convite', function () {
        User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        [, $token] = conviteComToken(['email' => 'mae@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteExistente())->assertRedirect('/responsavel/entrar?redirect='.urlencode("/responsavel/convite/{$token}"));
        expect(VinculoResponsavel::count())->toBe(0);
    });

    it('logado, só confirma as declarações e passa a ter 2 alunos', function () {
        $responsavel = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        User::factory()->comResponsavel($responsavel)->create();
        $aluno = User::factory()->aguardandoResponsavel()->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->actingAs($responsavel)->post("/responsavel/convite/{$token}", dadosDeAceiteExistente(['parentesco' => 'tutor']))->assertRedirect('/responsavel');

        expect($responsavel->alunos()->count())->toBe(2)->and($aluno->fresh()->status)->toBe('ativo');
    });

    it('recusa quem está logado com outro e-mail', function () {
        $outro = User::factory()->responsavel()->create();
        [, $token] = conviteComToken(['email' => 'mae@exemplo.test']);

        $this->actingAs($outro)->post("/responsavel/convite/{$token}", dadosDeAceiteExistente())->assertForbidden();
    });
});

describe('uso do token', function () {
    it('recusa o convite vencido', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'expira_em' => now()->subMinute()]);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertInertia(fn ($page) => $page->where('convite', null)->where('vencido', true));
        expect(VinculoResponsavel::count())->toBe(0)->and($aluno->fresh()->status)->toBe('aguardando_responsavel');
    });

    it('é de uso único', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertRedirect('/responsavel');
        $this->post('/logout');
        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['nome' => 'Outra']))
            ->assertInertia(fn ($page) => $page->where('convite', null)->where('vencido', false));

        expect(VinculoResponsavel::count())->toBe(1)->and(User::where('tipo', 'responsavel')->count())->toBe(1);
    });

    it('um convite invalidado (e-mail corrigido) não vale mais', function () {
        [, $token] = conviteComToken(['invalidado_em' => now()]);

        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('convite', null));
    });
});

describe('recusa', function () {
    it('mantém o aluno aguardando e avisa o aluno', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['email' => 'aluno@exemplo.test']);
        [$convite, $token] = conviteComToken(['aluno_id' => $aluno->id]);

        $this->post("/responsavel/convite/{$token}/recusar")->assertRedirect('/');

        expect($convite->fresh()->recusado_em)->not->toBeNull()->and($aluno->fresh()->status)->toBe('aguardando_responsavel');
        Mail::assertSent(AutorizacaoRecusada::class, fn ($m) => $m->hasTo('aluno@exemplo.test'));
        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('convite', null));
    });

    it('a tela de espera mostra recusado', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id]);
        $this->post("/responsavel/convite/{$token}/recusar");

        $this->actingAs($aluno)->get('/aguardando-responsavel')->assertInertia(fn ($page) => $page->where('recusado', true)->where('convite_expira_em', null));
    });
});

describe('co-responsável e transferência', function () {
    it('o co-responsável entra sem tirar o primeiro', function () {
        $primeiro = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($primeiro)->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'solicitante_id' => $primeiro->id, 'tipo' => 'coresponsavel', 'email' => 'pai@exemplo.test', 'parentesco' => 'pai']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['parentesco' => 'pai']))->assertRedirect('/responsavel');

        expect($aluno->responsaveis()->count())->toBe(2);
        Mail::assertNotSent(AlunoAutorizado::class);
    });

    it('a transferência só encerra o vínculo do antigo quando o novo aceita', function () {
        $antigo = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($antigo)->create();

        $this->actingAs($antigo)->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => 'novo@exemplo.test', 'tipo' => 'transferencia', 'parentesco' => 'tutor'])->assertSessionHasNoErrors();
        $convite = ConviteResponsavel::firstOrFail();
        expect($aluno->responsaveis()->pluck('users.id')->all())->toBe([$antigo->id]);

        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'solicitante_id' => $antigo->id, 'tipo' => 'transferencia', 'email' => 'novo@exemplo.test', 'substitui_responsavel_id' => $antigo->id]);
        $this->post('/logout');
        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['parentesco' => 'tutor']))->assertRedirect('/responsavel');

        $novo = User::firstWhere('email', 'novo@exemplo.test');
        expect($aluno->responsaveis()->pluck('users.id')->all())->toBe([$novo->id])
            ->and($antigo->fresh()->sem_aluno_desde)->not->toBeNull()
            ->and($aluno->fresh()->status)->toBe('ativo');
    });
});
