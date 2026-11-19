<?php

use App\Enums\Nivel;
use App\Models\User;

it('limita a zoeira por idade', function (int $idade, string $escolhida, string $efetiva) {
    $aluno = User::factory()->comIdade($idade)->comZoeira($escolhida)->make();

    expect($aluno->zoeiraEfetiva()->value)->toBe($efetiva);
})->with([
    '13 anos com sem filtro salvo' => [13, 'sem', 'sarc'],
    '14 anos com sem filtro salvo' => [14, 'sem', 'sarc'],
    '15 anos libera sem filtro' => [15, 'sem', 'sem'],
    '14 anos com fofo continua fofo' => [14, 'fofo', 'fofo'],
    '17 anos com sarc' => [17, 'sarc', 'sarc'],
]);

it('calcula o teto como menor idade possível', function () {
    $ano = (int) now()->format('Y') - 15;

    expect(User::idadePorAno($ano))->toBe(14)
        ->and(Nivel::tetoParaIdade(14))->toBe(Nivel::Sarc)
        ->and(Nivel::tetoParaIdade(15))->toBe(Nivel::Sem);
});

it('compartilha o teto no auth.user', function () {
    $aluno = User::factory()->comIdade(14)->comZoeira('sem')->make();

    expect($aluno->paraCompartilhar()['preferencias'])
        ->toMatchArray(['zoeira' => 'sarc', 'zoeira_maxima' => 'sarc']);
});
