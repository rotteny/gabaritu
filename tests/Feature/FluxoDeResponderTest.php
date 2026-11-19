<?php

use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Resposta;
use App\Models\RevisaoEspacada;
use App\Models\Tentativa;
use App\Models\User;

beforeEach(function () {
    $this->c = conteudoDeTeste(3);
    $this->aluno = User::factory()->create();
    criarFala('acerto', 'acertou!', 'fofo');
    criarFala('erro', 'errou!', 'fofo');
    criarFala('boas_vindas', 'oi', 'fofo');
});

it('percorre o fluxo: abre, responde, termina e mostra o resultado', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    foreach ([true, false, true] as $certa) {
        $r = responderAtual($this->aluno, $tentativa, $certa)->assertOk();
        $r->assertJsonStructure(['correta', 'alternativa_correta_id', 'etiqueta', 'explicacao', 'fala' => ['texto', 'humor', 'variacao_id'], 'freio_ativo', 'chute', 'proxima']);
        expect($r->json('correta'))->toBe($certa)->and($r->json('chute'))->toBeFalse();
    }

    expect($r->json('proxima'))->toHaveKey('resultado_url');
    $this->get("/tentativas/{$tentativa->id}/resultado")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Resultado')
            ->where('acertos', 2)->where('total', 3)->where('tem_erradas', true)
            ->where('tentativa_id', $tentativa->id)->where('nota_assunto', null)
            ->where('erro_mais_comum.nome', 'Erro de teste')->where('erro_mais_comum.vezes', 1));
});

it('mostra a questão sem vazar o gabarito e com posição e total', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    $this->actingAs($this->aluno)->get("/tentativas/{$tentativa->id}")
        ->assertInertia(fn ($page) => $page->component('Questao')
            ->where('tentativa.posicao', 1)->where('tentativa.total', 3)
            ->where('tentativa.tipo', 'questionario')
            ->has('questao.alternativas', 5)
            ->missing('questao.alternativas.0.correta'));
});

it('embaralha as alternativas por tentativa com ordem estável entre recarregamentos', function () {
    $a = iniciarTentativa($this->aluno, $this->c['questionario']);
    $ordem = fn (Tentativa $t) => collect(abrirQuestaoAtual($this->aluno, $t)['alternativas'])->pluck('id')->all();

    expect($ordem($a))->toBe($ordem($a));

    $ordens = collect(range(1, 8))->map(function () use ($ordem) {
        $t = Tentativa::create(['user_id' => $this->aluno->id, 'questionario_id' => $this->c['questionario']->id, 'tipo' => 'questionario', 'seed' => random_int(1, 1_000_000), 'iniciada_em' => now()]);
        $t->itens()->create(['questao_id' => $this->c['questoes'][0]->id, 'posicao' => 1]);

        return $ordem($t);
    });

    expect($ordens->unique()->count())->toBeGreaterThan(1);
});

it('responde 422 para questão já respondida, alternativa de outra questão ou não entregue', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);
    $primeira = abrirQuestaoAtual($this->aluno, $tentativa);
    $this->travel(10)->seconds();
    $url = "/tentativas/{$tentativa->id}/respostas";

    $this->postJson($url, ['questao_id' => $this->c['questoes'][1]->id, 'alternativa_id' => $primeira['alternativas'][0]['id']])->assertStatus(422);
    $this->postJson($url, ['questao_id' => $primeira['id'], 'alternativa_id' => $this->c['questoes'][1]->alternativas()->first()->id])->assertStatus(422);

    $payload = ['questao_id' => $primeira['id'], 'alternativa_id' => $primeira['alternativas'][0]['id']];
    $this->postJson($url, $payload)->assertOk();
    $this->postJson($url, $payload)->assertStatus(422)->assertJsonValidationErrors('questao_id');
});

it('grava resposta, snapshot da nota e entra na revisão ao errar', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    responderAtual($this->aluno, $tentativa, false)->assertOk();

    $resposta = Resposta::firstOrFail();
    expect($resposta->correta)->toBeFalse()->and($resposta->tempo_ms)->toBeGreaterThanOrEqual(10_000)
        ->and(DesempenhoAssuntoHistorico::count())->toBe(1)
        ->and(RevisaoEspacada::firstOrFail()->caixa)->toBe(1);
});

it('exibe a nota do assunto depois de 6 respostas distintas', function () {
    $c = conteudoDeTeste(6, 2, 'b');
    $tentativa = iniciarTentativa($this->aluno, $c['questionario']);

    foreach (range(1, 6) as $_) {
        responderAtual($this->aluno, $tentativa, true);
    }

    $this->get('/materias/'.$c['materia']->slug)->assertInertia(fn ($page) => $page
        ->where('assuntos.0.nota', fn ($nota) => $nota >= 500)->where('assuntos.0.amostra_baixa', true)
        ->where('assuntos.0.questionarios.0.melhor_acertos', 6));
    $this->get("/tentativas/{$tentativa->id}/resultado")->assertInertia(fn ($page) => $page->where('nota_assunto.antes', null)->has('nota_assunto.depois'));
});

it('cria a revisão das erradas pela rota do contrato', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);
    foreach ([false, true, false] as $certa) {
        responderAtual($this->aluno, $tentativa, $certa);
    }

    $this->post("/tentativas/{$tentativa->id}/revisao")->assertRedirect();

    $revisao = Tentativa::where('tipo', 'revisao_erradas')->firstOrFail();
    expect($revisao->itens)->toHaveCount(2);
});

it('limita a revisão do dia a 10 questões, mais atrasadas primeiro', function () {
    $c = conteudoDeTeste(12, 2, 'c');
    foreach ($c['questoes'] as $i => $questao) {
        RevisaoEspacada::create(['user_id' => $this->aluno->id, 'questao_id' => $questao->id, 'caixa' => 1, 'proxima_em' => now()->subDays(20 - $i)->toDateString()]);
    }

    $this->actingAs($this->aluno)->get('/revisao')->assertRedirect();

    $revisao = Tentativa::where('tipo', 'revisao')->firstOrFail();
    expect($revisao->itens)->toHaveCount(10)
        ->and($revisao->itens->pluck('questao_id')->all())->toBe($c['questoes']->take(10)->pluck('id')->all());
    $this->get('/inicio')->assertInertia(fn ($page) => $page->where('revisao_hoje_total', 10)->where('revisao_amanha', 2));
});

it('manda para o início com fala de vazio quando não há o que revisar', function () {
    criarFala('vazio:revisao', 'nada pra revisar', 'fofo');

    $this->actingAs($this->aluno)->get('/revisao')->assertRedirect('/inicio');
    $this->get('/inicio')->assertInertia(fn ($page) => $page->where('fala.texto', 'nada pra revisar'));
});
