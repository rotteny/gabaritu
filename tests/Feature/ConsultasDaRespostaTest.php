<?php

use App\Models\Alternativa;
use App\Models\Questao;
use App\Models\User;
use Illuminate\Support\Facades\DB;

const LIMITE_DE_CONSULTAS_DO_POST = 22;

beforeEach(function () {
    $this->c = conteudoDeTeste(3);
    $this->aluno = User::factory()->create();
    criarFala('acerto', 'acertou!', 'fofo');
    criarFala('erro', 'errou!', 'fofo');
});

it('responde uma questão sem estourar o orçamento de consultas', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);
    $questao = abrirQuestaoAtual($this->aluno, $tentativa);
    $certa = Alternativa::where('questao_id', Questao::findOrFail($questao['id'])->id)->where('correta', true)->firstOrFail();
    $this->travel(10)->seconds();

    DB::enableQueryLog();
    $this->actingAs($this->aluno)
        ->postJson("/tentativas/{$tentativa->id}/respostas", ['questao_id' => $questao['id'], 'alternativa_id' => $certa->id])
        ->assertOk();
    $total = count(DB::getQueryLog());

    expect($total)->toBeLessThanOrEqual(LIMITE_DE_CONSULTAS_DO_POST);
});
