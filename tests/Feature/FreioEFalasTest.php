<?php

use App\Domain\Falas\FalaService;
use App\Models\User;

beforeEach(function () {
    $this->c = conteudoDeTeste(8);
    $this->aluno = User::factory()->comZoeira('sem')->comIdade(17)->create();
    criarFala('acerto', 'acertou', 'fofo');
    criarFala('erro', 'errou', 'sem');
    criarFala('freio', 'calma, modo ajuda', 'fofo');
    criarFala('freio_saida', 'voltou ao normal', 'fofo');
});

it('liga o modo ajuda no 3º erro seguido e sai após 2 acertos', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    $r1 = responderAtual($this->aluno, $tentativa, false);
    $r2 = responderAtual($this->aluno, $tentativa, false);
    $r3 = responderAtual($this->aluno, $tentativa, false);
    $r4 = responderAtual($this->aluno, $tentativa, true);
    $r5 = responderAtual($this->aluno, $tentativa, true);

    expect([$r1->json('freio_ativo'), $r2->json('freio_ativo')])->toBe([false, false])
        ->and($r1->json('fala.texto'))->toBe('errou')
        ->and($r3->json('freio_ativo'))->toBeTrue()
        ->and($r3->json('fala'))->toMatchArray(['texto' => 'calma, modo ajuda', 'humor' => 'ajuda'])
        ->and($r4->json('freio_ativo'))->toBeTrue()
        ->and($r5->json('freio_ativo'))->toBeFalse()
        ->and($r5->json('fala.texto'))->toBe('voltou ao normal');
});

it('um acerto no meio zera a contagem de erros seguidos', function () {
    $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);

    foreach ([false, false, true, false, false] as $certa) {
        $r = responderAtual($this->aluno, $tentativa, $certa);
    }

    expect($r->json('freio_ativo'))->toBeFalse();
});

it('não repete falas recentes do aluno e cai na reserva quando acabam', function () {
    foreach (['um', 'dois', 'tres'] as $texto) {
        criarFala('inicio', $texto);
    }
    criarFala('inicio', 'reserva', reserva: true);
    $this->actingAs($this->aluno);

    $textos = collect(range(1, 4))->map(function () {
        $texto = null;
        $this->get('/inicio')->assertInertia(function ($page) use (&$texto) {
            $texto = $page->toArray()['props']['fala']['texto'];
        });

        return $texto;
    });

    expect($textos->take(3)->unique())->toHaveCount(3)->and($textos[3])->toBe('reserva');
});

it('respeita o teto de zoeira por idade ao sortear', function () {
    $menino = User::factory()->comIdade(14)->comZoeira('sem')->create();
    criarFala('y', 'fofinho', 'fofo');
    criarFala('y', 'ironico', 'sarc');
    criarFala('y', 'sem filtro', 'sem', idadeMinima: 15);

    $textos = collect(range(1, 20))->map(fn () => app(FalaService::class)->sortear($menino, ['y'], 'neutro')->texto);

    expect($textos->contains('sem filtro'))->toBeFalse();

    $velho = User::factory()->comIdade(15)->comZoeira('sem')->create();
    $textos = collect(range(1, 60))->map(fn () => app(FalaService::class)->sortear($velho, ['y'], 'neutro')->texto);
    expect($textos->contains('sem filtro'))->toBeTrue();
});

it('ignora variação vencida e ainda não vigente', function () {
    criarFala('z', 'vencida')->update(['validade_em' => now()->subDay()->toDateString()]);
    criarFala('z', 'futura')->update(['inicio_em' => now()->addDay()->toDateString()]);
    criarFala('z', 'valida');

    $texto = app(FalaService::class)->sortear($this->aluno, ['z'], 'neutro')->texto;

    expect($texto)->toBe('valida');
});
