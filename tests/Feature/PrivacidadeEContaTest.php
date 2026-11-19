<?php

use App\Models\DesempenhoAssunto;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\FeedbackFala;
use App\Models\Resposta;
use App\Models\RevisaoEspacada;
use App\Models\Tentativa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

describe('isolamento entre alunos', function () {
    it('só deixa o dono ver, responder e pedir revisão da própria tentativa', function () {
        $c = conteudoDeTeste(2);
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $tentativa = iniciarTentativa($dono, $c['questionario']);
        responderAtual($dono, $tentativa, true);

        $this->actingAs($outro)->get("/tentativas/{$tentativa->id}")->assertNotFound();
        $this->get("/tentativas/{$tentativa->id}/resultado")->assertNotFound();
        $this->post("/tentativas/{$tentativa->id}/revisao")->assertNotFound();
        $this->postJson("/tentativas/{$tentativa->id}/respostas", ['questao_id' => 1, 'alternativa_id' => 1])->assertNotFound();
        $this->actingAs($dono)->get("/tentativas/{$tentativa->id}")->assertOk();
    });

    it('exige login nas rotas do aluno', function () {
        $this->get('/inicio')->assertRedirect('/entrar');
        $this->get('/desempenho')->assertRedirect('/entrar');
    });
});

describe('conta', function () {
    it('salva preferências, perfil e senha', function () {
        $aluno = User::factory()->comIdade(16)->create(['password' => 'senha-antiga-123']);
        $this->actingAs($aluno);

        $this->patch('/conta/preferencias', ['zoeira' => 'sem', 'som' => false, 'reduzir_movimento' => true])->assertSessionHasNoErrors();
        $this->patch('/conta/perfil', ['apelido' => 'Novo', 'serie' => '3em'])->assertSessionHasNoErrors();
        $this->put('/conta/senha', ['senha_atual' => 'senha-antiga-123', 'senha' => 'senha-nova-456', 'senha_confirmation' => 'senha-nova-456'])->assertSessionHasNoErrors();

        $aluno->refresh();
        expect([$aluno->zoeira, $aluno->som, $aluno->reduzir_movimento, $aluno->apelido, $aluno->serie])->toBe(['sem', false, true, 'Novo', '3em'])
            ->and(Hash::check('senha-nova-456', $aluno->password))->toBeTrue();
    });

    it('recusa nível acima do teto de idade e senha atual errada', function () {
        $aluno = User::factory()->comIdade(14)->create();
        $this->actingAs($aluno);

        $this->patch('/conta/preferencias', ['zoeira' => 'sem'])->assertSessionHasErrors('zoeira');
        $this->put('/conta/senha', ['senha_atual' => 'errada', 'senha' => 'senha-nova-456', 'senha_confirmation' => 'senha-nova-456'])->assertSessionHasErrors('senha_atual');
        expect($aluno->refresh()->zoeira)->not->toBe('sem');
    });

    it('não apaga a conta com a senha errada', function () {
        $aluno = User::factory()->create();

        $this->actingAs($aluno)->delete('/conta', ['senha' => 'errada'])->assertSessionHasErrors('senha');

        expect(User::count())->toBe(1);
    });

    it('apaga a conta em cascata com a senha certa (caso 19)', function () {
        $c = conteudoDeTeste(3);
        $aluno = User::factory()->create(['password' => 'minha-senha-123']);
        $outro = User::factory()->create();
        $tentativa = iniciarTentativa($aluno, $c['questionario']);
        responderAtual($aluno, $tentativa, false);
        $variacao = criarFala('k', 'texto');
        FeedbackFala::create(['user_id' => $aluno->id, 'variacao_fala_id' => $variacao->id, 'legal' => true]);
        $outraTentativa = iniciarTentativa($outro, $c['questionario']);
        responderAtual($outro, $outraTentativa, true);

        $this->actingAs($aluno)->withSession(['freio' => ['errosSeguidos' => 2]])
            ->delete('/conta', ['senha' => 'minha-senha-123'])->assertRedirect('/');

        $this->assertGuest();
        expect(User::where('id', $aluno->id)->exists())->toBeFalse();
        foreach ([Tentativa::class, Resposta::class, DesempenhoAssunto::class, DesempenhoAssuntoHistorico::class, RevisaoEspacada::class, FeedbackFala::class] as $modelo) {
            expect($modelo::where('user_id', $aluno->id)->count())->toBe(0, $modelo);
        }
        expect(Resposta::where('user_id', $outro->id)->count())->toBe(1)
            ->and(DB::table('sessions')->where('user_id', $aluno->id)->count())->toBe(0);
    });
});

describe('páginas públicas e erros', function () {
    it('serve a privacidade sem login', function () {
        criarFala('privacidade', 'piada de privacidade');

        $this->get('/privacidade')->assertOk()->assertInertia(fn ($page) => $page->component('Privacidade')->where('fala.texto', 'piada de privacidade'));
    });

    it('renderiza a página Erro com status e fala no 404', function () {
        criarFala('erro_http:404', 'sumiu');

        $this->get('/rota-que-nao-existe')->assertNotFound()
            ->assertInertia(fn ($page) => $page->component('Erro')->where('status', 404)->where('fala.texto', 'sumiu'));
    });

    it('compartilha auth.user e gabaritu nas props', function () {
        $aluno = User::factory()->comIdade(14)->comZoeira('sem')->create();
        criarFala('inicio', 'oi');

        $this->actingAs($aluno)->get('/inicio')->assertInertia(fn ($page) => $page
            ->where('auth.user.apelido', $aluno->apelido)->where('auth.user.preferencias.zoeira', 'sarc')
            ->where('auth.user.preferencias.zoeira_maxima', 'sarc')->has('gabaritu.email_contato'));
    });
});
