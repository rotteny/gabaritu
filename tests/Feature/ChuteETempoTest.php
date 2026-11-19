<?php

use App\Models\DesempenhoAssunto;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Resposta;
use App\Models\RevisaoEspacada;
use App\Models\TentativaQuestao;
use App\Models\User;

beforeEach(function () {
    $this->c = conteudoDeTeste(2);
    $this->aluno = User::factory()->create();
});

it('grava servida_em no servidor ao entregar a questão e não o reinicia ao recarregar', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);
    expect(TentativaQuestao::whereNotNull('servida_em')->count())->toBe(0);

    abrirQuestaoAtual($this->aluno, $tentativa);
    $primeira = TentativaQuestao::whereNotNull('servida_em')->firstOrFail()->servida_em;
    $this->travel(2)->seconds();
    abrirQuestaoAtual($this->aluno, $tentativa);

    expect(TentativaQuestao::whereNotNull('servida_em')->firstOrFail()->servida_em->equalTo($primeira))->toBeTrue();
});

it('marca chute abaixo do limite: sem nota, sem snapshot, e erro entra na revisão', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    $r = responderAtual($this->aluno, $tentativa, false, esperarMs: 1000)->assertOk();

    expect($r->json('chute'))->toBeTrue()
        ->and(Resposta::firstOrFail()->chute)->toBeTrue()
        ->and(DesempenhoAssunto::count())->toBe(0)
        ->and(DesempenhoAssuntoHistorico::count())->toBe(0)
        ->and(RevisaoEspacada::count())->toBe(1);
});

it('não marca chute a partir do limite da questão', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    $r = responderAtual($this->aluno, $tentativa, true, esperarMs: 3000)->assertOk();

    expect($r->json('chute'))->toBeFalse()->and(DesempenhoAssuntoHistorico::count())->toBe(1);
});
