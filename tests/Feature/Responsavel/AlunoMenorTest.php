<?php

use App\Mail\ConviteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function alunoPendenteComConvite(): User
{
    test()->post('/cadastro', dadosDeCadastro([
        'ano_nascimento' => (int) now()->format('Y') - 16,
        'email_responsavel' => 'mae@exemplo.test',
        'parentesco_responsavel' => 'mae',
    ]));

    return User::firstWhere('email', 'aluno@exemplo.test');
}

describe('bloqueio do aluno pendente', function () {
    it('redireciona toda rota de aluno para a tela de espera', function (string $metodo, string $rota) {
        $aluno = User::factory()->aguardandoResponsavel()->create();

        $this->actingAs($aluno)->{$metodo}($rota)->assertRedirect('/aguardando-responsavel');
    })->with([
        ['get', '/inicio'], ['get', '/desempenho'], ['get', '/revisao'], ['patch', '/conta/preferencias'], ['post', '/conta/responsaveis'],
    ]);

    it('deixa o pendente abrir /conta, apagar a conta e ver as páginas públicas', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['password' => 'minha-senha-123']);
        $this->actingAs($aluno);

        $this->get('/conta')->assertOk();
        $this->get('/termos')->assertOk();
        $this->get('/privacidade')->assertOk();
        $this->delete('/conta', ['senha' => 'minha-senha-123'])->assertRedirect('/');
        expect(User::find($aluno->id))->toBeNull();
    });

    it('o aluno ativo não vê a tela de espera', function () {
        $this->actingAs(User::factory()->create())->get('/aguardando-responsavel')->assertRedirect('/inicio');
    });
});

describe('tela de espera', function () {
    it('mostra o e-mail mascarado, o prazo e os reenvios restantes', function () {
        $aluno = alunoPendenteComConvite();

        $this->actingAs($aluno)->get('/aguardando-responsavel')->assertOk()->assertInertia(fn ($page) => $page
            ->component('aluno/AguardandoResponsavel')
            ->where('email_responsavel_mascarado', 'ma***@exemplo.test')
            ->where('recusado', false)
            ->where('reenvios_restantes', 3)
            ->has('convite_expira_em')
            ->has('fala.texto'));
    });

    it('reenvia no máximo 3 vezes por dia e invalida o convite anterior', function () {
        $aluno = alunoPendenteComConvite();
        $this->actingAs($aluno);

        foreach ([2, 1, 0] as $restantes) {
            $this->post('/aguardando-responsavel/reenviar')->assertSessionHasNoErrors();
            $this->get('/aguardando-responsavel')->assertInertia(fn ($page) => $page->where('reenvios_restantes', $restantes));
        }
        $this->post('/aguardando-responsavel/reenviar')->assertSessionHasErrors('reenvio');

        expect(ConviteResponsavel::pendentes()->count())->toBe(1)->and(ConviteResponsavel::count())->toBe(4);
        $this->travel(25)->hours();
        $this->post('/aguardando-responsavel/reenviar')->assertSessionHasNoErrors();
    });

    it('corrigir o e-mail invalida o convite antigo e envia ao novo', function () {
        $aluno = alunoPendenteComConvite();
        $antigo = ConviteResponsavel::firstOrFail();

        $this->actingAs($aluno)->patch('/aguardando-responsavel/email', ['email_responsavel' => 'novo@exemplo.test'])->assertSessionHasNoErrors();

        expect($antigo->fresh()->invalidado_em)->not->toBeNull()->and($aluno->fresh()->email_responsavel)->toBe('novo@exemplo.test');
        Mail::assertSent(ConviteDeResponsavel::class, fn ($m) => $m->hasTo('novo@exemplo.test'));
    });

    it('recusa corrigir para o próprio e-mail', function () {
        $aluno = alunoPendenteComConvite();

        $this->actingAs($aluno)->patch('/aguardando-responsavel/email', ['email_responsavel' => 'ALUNO@exemplo.test'])->assertSessionHasErrors('email_responsavel');
    });
});

describe('conta do aluno acompanhado', function () {
    it('mostra os responsáveis com parentesco, convites pendentes e pode_convidar', function () {
        $responsavel = User::factory()->responsavel()->create(['apelido' => 'Dona Marta', 'email' => 'marta@exemplo.test']);
        $aluno = User::factory()->comResponsavel($responsavel, 'avo')->create();
        $convite = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel_aluno', 'email' => 'tio@exemplo.test', 'parentesco' => 'outro', 'parentesco_outro' => 'tio']);

        $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page
            ->where('responsaveis.0.nome', 'Dona Marta')
            ->where('responsaveis.0.parentesco', 'avo')
            ->where('responsaveis.0.email_mascarado', 'ma***@exemplo.test')
            ->where('convites_responsavel.0.id', $convite->id)
            ->where('convites_responsavel.0.parentesco', 'outro')
            ->where('pode_convidar', true));
    });

    it('o aluno pede outro responsável (convite coresponsavel_aluno) e cancela', function () {
        $aluno = User::factory()->comResponsavel()->create();
        $this->actingAs($aluno);

        $this->post('/conta/responsaveis', ['email' => 'Tio@exemplo.test', 'parentesco' => 'outro', 'parentesco_outro' => 'tio'])->assertSessionHasNoErrors();
        $convite = ConviteResponsavel::firstOrFail();
        expect($convite->tipo)->toBe('coresponsavel_aluno')->and($convite->email)->toBe('tio@exemplo.test')->and($convite->solicitante_id)->toBe($aluno->id);
        Mail::assertSent(ConviteDeResponsavel::class, fn ($m) => $m->hasTo('tio@exemplo.test'));

        $this->delete("/conta/responsaveis/convites/{$convite->id}")->assertRedirect();
        expect($convite->fresh()->estaPendente())->toBeFalse();
    });

    it('limita a 3 convites por dia', function () {
        $aluno = User::factory()->comResponsavel()->create();
        $this->actingAs($aluno);

        foreach (range(1, 3) as $i) {
            $this->post('/conta/responsaveis', ['email' => "r{$i}@exemplo.test", 'parentesco' => 'pai'])->assertSessionHasNoErrors();
        }
        $this->post('/conta/responsaveis', ['email' => 'r4@exemplo.test', 'parentesco' => 'pai'])->assertSessionHasErrors('email');
        $this->get('/conta')->assertInertia(fn ($page) => $page->where('pode_convidar', false));
    });

    it('limita a 5 responsáveis por aluno', function () {
        $aluno = User::factory()->comResponsavel()->create();
        VinculoResponsavel::factory()->count(3)->create(['aluno_id' => $aluno->id]);
        $this->actingAs($aluno);

        $this->post('/conta/responsaveis', ['email' => 'a@exemplo.test', 'parentesco' => 'pai'])->assertSessionHasNoErrors();
        $this->post('/conta/responsaveis', ['email' => 'b@exemplo.test', 'parentesco' => 'pai'])->assertSessionHasErrors('email');
    });

    it('o aluno não tem rota para remover responsável ativo', function () {
        $responsavel = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($responsavel)->create();

        $this->actingAs($aluno)->delete("/conta/responsaveis/{$responsavel->id}")->assertStatus(405);
        $this->delete("/responsavel/alunos/{$aluno->id}/vinculo")->assertNotFound();
        expect($aluno->responsaveis()->count())->toBe(1);
    });

    it('o convite do aluno é aceito pelo mesmo fluxo e vira vínculo', function () {
        $aluno = User::factory()->comResponsavel()->create();
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel_aluno', 'email' => 'tio@exemplo.test', 'parentesco' => 'outro', 'parentesco_outro' => 'tio']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['parentesco' => 'outro', 'parentesco_outro' => 'tio']))->assertRedirect('/responsavel');

        $vinculo = VinculoResponsavel::query()->where('aluno_id', $aluno->id)->latest('id')->first();
        expect($vinculo->parentesco)->toBe('outro')->and($vinculo->parentesco_outro)->toBe('tio')->and($aluno->responsaveis()->count())->toBe(2);
    });
});
