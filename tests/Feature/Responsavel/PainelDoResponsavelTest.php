<?php

use App\Models\ConviteResponsavel;
use App\Models\DesempenhoAssunto;
use App\Models\Tentativa;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function responsavelComAluno(array $aluno = []): array
{
    $responsavel = User::factory()->responsavel()->create(['password' => 'minha-senha-123']);
    $filho = User::factory()->comResponsavel($responsavel)->create($aluno);

    return [$responsavel, $filho];
}

it('lista os alunos com responsáveis, convites pendentes e notas', function () {
    [$responsavel, $aluno] = responsavelComAluno(['apelido' => 'Joaozinho', 'serie' => '1em', 'zoeira_responsavel' => 'sarc']);
    $c = conteudoDeTeste(2);
    DesempenhoAssunto::query()->insert(['user_id' => $aluno->id, 'assunto_id' => $c['assunto']->id, 'theta' => 1.0, 'n_primeiras' => 20, 'nota' => 600]);
    ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel', 'email' => 'tio@exemplo.test']);

    $this->actingAs($responsavel)->get('/responsavel')->assertOk()->assertInertia(fn ($page) => $page
        ->component('responsavel/Painel')
        ->has('alunos', 1)
        ->where('alunos.0.id', $aluno->id)
        ->where('alunos.0.apelido', 'Joaozinho')
        ->where('alunos.0.status', 'ativo')
        ->where('alunos.0.teto_zoeira_responsavel', 'sarc')
        ->where('alunos.0.responsaveis.0.sou_eu', true)
        ->where('alunos.0.responsaveis.0.parentesco', 'mae')
        ->where('alunos.0.convites_pendentes.0.tipo', 'coresponsavel')
        ->where('alunos.0.convites_pendentes.0.email_mascarado', 'ti***@exemplo.test')
        ->where('alunos.0.notas.0.slug', $c['materia']->slug)
        ->where('alunos.0.notas.0.nome', $c['materia']->nome)
        ->has('alunos.0.notas.0.nota')
        ->has('fala.texto'));
});

it('o responsável vê o desempenho com o mesmo payload do /desempenho', function () {
    [$responsavel, $aluno] = responsavelComAluno();

    $doAluno = $this->actingAs($aluno)->get('/desempenho')->inertiaProps();
    $doResponsavel = $this->actingAs($responsavel)->get("/responsavel/alunos/{$aluno->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->component('responsavel/Aluno')->where('aluno.id', $aluno->id))
        ->inertiaProps();

    expect($doResponsavel['materias'])->toBe($doAluno['materias'])->and($doResponsavel['revisao'])->toBe($doAluno['revisao']);
});

describe('papéis', function () {
    it('aluno não acessa /responsavel e responsável não acessa rotas de aluno', function () {
        [$responsavel, $aluno] = responsavelComAluno();

        $this->actingAs($aluno)->get('/responsavel')->assertNotFound();
        $this->actingAs($aluno)->get('/responsavel/conta')->assertNotFound();
        $this->actingAs($aluno)->get("/responsavel/alunos/{$aluno->id}")->assertNotFound();
        $this->actingAs($responsavel)->get('/inicio')->assertNotFound();
        $this->get('/desempenho')->assertNotFound();
        $this->get('/conta')->assertNotFound();
        $this->get('/aguardando-responsavel')->assertNotFound();
        $this->post('/conta/responsaveis', ['email' => 'x@exemplo.test', 'parentesco' => 'pai'])->assertNotFound();
    });

    it('exige login nas rotas do responsável', function () {
        $this->get('/responsavel')->assertRedirect('/responsavel/entrar');
        $this->get('/responsavel/conta')->assertRedirect('/responsavel/entrar');
        $this->get('/inicio')->assertRedirect('/entrar');
    });

    it('expõe tipo e status no auth.user', function () {
        [$responsavel] = responsavelComAluno();

        $this->actingAs($responsavel)->get('/responsavel')->assertInertia(fn ($page) => $page->where('auth.user.tipo', 'responsavel')->where('auth.user.status', 'ativo'));
    });
});

describe('IDOR entre responsáveis', function () {
    it('não deixa um responsável agir sobre aluno de outro', function () {
        [, $alheio] = responsavelComAluno();
        [$intruso] = responsavelComAluno();
        $this->actingAs($intruso);

        $this->get("/responsavel/alunos/{$alheio->id}")->assertNotFound();
        $this->patch("/responsavel/alunos/{$alheio->id}/zoeira", ['teto' => 'fofo'])->assertNotFound();
        $this->post("/responsavel/alunos/{$alheio->id}/convites", ['email' => 'x@exemplo.test', 'tipo' => 'coresponsavel', 'parentesco' => 'pai'])->assertNotFound();
        $this->delete("/responsavel/alunos/{$alheio->id}/vinculo")->assertNotFound();
        $this->delete("/responsavel/alunos/{$alheio->id}", ['senha' => 'minha-senha-123'])->assertNotFound();

        expect($alheio->fresh())->not->toBeNull()->and($alheio->fresh()->zoeira_responsavel)->toBeNull();
        expect(ConviteResponsavel::count())->toBe(0);
    });

    it('um vínculo revogado deixa de dar acesso', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id]);
        $this->actingAs($responsavel)->delete("/responsavel/alunos/{$aluno->id}/vinculo")->assertRedirect('/responsavel');

        $this->get("/responsavel/alunos/{$aluno->id}")->assertNotFound();
    });

    it('o aluno não cancela convite de outro aluno', function () {
        $convite = ConviteResponsavel::factory()->create(['tipo' => 'coresponsavel_aluno']);
        $outro = User::factory()->comResponsavel()->create();

        $this->actingAs($outro)->delete("/conta/responsaveis/convites/{$convite->id}")->assertNotFound();
        expect($convite->fresh()->invalidado_em)->toBeNull();
    });
});

describe('revogação', function () {
    it('bloqueia revogar o único vínculo do menor com 422', function () {
        [$responsavel, $aluno] = responsavelComAluno();

        $this->actingAs($responsavel)->deleteJson("/responsavel/alunos/{$aluno->id}/vinculo")
            ->assertStatus(422)->assertJsonValidationErrors('vinculo');

        expect($aluno->responsaveis()->count())->toBe(1)->and($aluno->fresh()->status)->toBe('ativo');
    });

    it('revoga quando há outro responsável, e o aluno segue ativo', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id]);

        $this->actingAs($responsavel)->delete("/responsavel/alunos/{$aluno->id}/vinculo")->assertRedirect('/responsavel');

        expect($aluno->responsaveis()->count())->toBe(1)->and($aluno->fresh()->status)->toBe('ativo')
            ->and($responsavel->fresh()->sem_aluno_desde)->not->toBeNull();
    });

    it('nenhum aluno menor ativo fica com zero vínculos', function () {
        [$responsavel, $aluno] = responsavelComAluno();

        $this->actingAs($responsavel)->deleteJson("/responsavel/alunos/{$aluno->id}/vinculo");
        $this->deleteJson('/responsavel/conta', ['senha' => 'minha-senha-123'])->assertStatus(422);

        expect(User::query()->where('tipo', 'aluno')->where('status', 'ativo')->doesntHave('vinculosComResponsaveis')->count())->toBe(0);
    });
});

describe('apagar o aluno', function () {
    it('exige a senha do responsável e apaga tudo do aluno', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        $c = conteudoDeTeste(2);
        iniciarTentativa($aluno, $c['questionario']);
        $this->actingAs($responsavel);

        $this->delete("/responsavel/alunos/{$aluno->id}", ['senha' => 'errada'])->assertSessionHasErrors('senha');
        expect(User::find($aluno->id))->not->toBeNull();

        $this->delete("/responsavel/alunos/{$aluno->id}", ['senha' => 'minha-senha-123'])->assertRedirect('/responsavel');
        expect(User::find($aluno->id))->toBeNull()
            ->and(VinculoResponsavel::count())->toBe(0)
            ->and(Tentativa::count())->toBe(0)
            ->and($responsavel->fresh()->sem_aluno_desde)->not->toBeNull();
    });
});

describe('convidar', function () {
    it('envia co-responsável e respeita o limite de 5 responsáveis', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        $this->actingAs($responsavel);

        foreach (range(1, 4) as $i) {
            $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => "r{$i}@exemplo.test", 'tipo' => 'coresponsavel', 'parentesco' => 'pai'])->assertSessionHasNoErrors();
        }
        $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => 'r5@exemplo.test', 'tipo' => 'coresponsavel', 'parentesco' => 'pai'])->assertSessionHasErrors('email');
        expect(ConviteResponsavel::count())->toBe(4);
    });

    it('recusa convidar quem já é responsável e tipos inválidos', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        $this->actingAs($responsavel);

        $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => $responsavel->email, 'tipo' => 'coresponsavel', 'parentesco' => 'pai'])->assertSessionHasErrors('email');
        $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => 'x@exemplo.test', 'tipo' => 'autorizacao', 'parentesco' => 'pai'])->assertSessionHasErrors('tipo');
    });
});

describe('conta do responsável', function () {
    it('mostra os dados e os alunos em que é o único responsável', function () {
        [$responsavel, $aluno] = responsavelComAluno(['apelido' => 'Joaozinho']);

        $this->actingAs($responsavel)->get('/responsavel/conta')->assertInertia(fn ($page) => $page
            ->component('responsavel/Conta')
            ->where('responsavel.email', $responsavel->email)
            ->where('responsavel.ano_nascimento', $responsavel->ano_nascimento)
            ->where('alunos_unicos', ['Joaozinho']));
    });

    it('troca a senha e apaga a própria conta só se não for o único responsável', function () {
        [$responsavel, $aluno] = responsavelComAluno();
        $this->actingAs($responsavel);

        $this->put('/responsavel/conta/senha', ['senha_atual' => 'minha-senha-123', 'senha' => 'outra-senha-456', 'senha_confirmation' => 'outra-senha-456'])->assertSessionHasNoErrors();
        $this->delete('/responsavel/conta', ['senha' => 'outra-senha-456'])->assertSessionHasErrors('conta');

        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id]);
        $this->delete('/responsavel/conta', ['senha' => 'outra-senha-456'])->assertRedirect('/');

        expect(User::find($responsavel->id))->toBeNull()->and($aluno->responsaveis()->count())->toBe(1);
        $this->assertGuest();
    });
});
