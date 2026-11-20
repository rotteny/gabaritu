<?php

use App\Domain\Falas\FalaService;
use App\Enums\Nivel;
use App\Models\User;

function alunoComTeto(int $idade, ?string $tetoDoResponsavel, string $preferencia = 'sem'): User
{
    return User::factory()->comIdade($idade)->comResponsavel()->create(['zoeira' => $preferencia, 'zoeira_responsavel' => $tetoDoResponsavel]);
}

it('o teto efetivo é o menor entre o da idade e o do responsável', function (int $idade, ?string $responsavel, string $esperado) {
    expect(alunoComTeto($idade, $responsavel)->zoeiraMaxima()->value)->toBe($esperado);
})->with([
    '14 sem teto do responsável' => [14, null, 'sarc'],
    '14 com fofo' => [14, 'fofo', 'fofo'],
    '16 sem teto' => [16, null, 'sem'],
    '16 com sarc' => [16, 'sarc', 'sarc'],
    '16 com fofo' => [16, 'fofo', 'fofo'],
    '14 com sarc não passa da idade' => [14, 'sarc', 'sarc'],
]);

it('o sorteio de falas respeita o teto do responsável', function () {
    criarFala('acerto', 'texto fofo', 'fofo');
    criarFala('acerto', 'texto sarc', 'sarc');
    criarFala('acerto', 'texto sem filtro', 'sem');
    $aluno = alunoComTeto(16, 'fofo');

    $textos = collect(range(1, 25))->map(fn () => app(FalaService::class)->sortear($aluno, ['acerto'], 'feliz')->texto)->unique()->all();

    expect($textos)->toBe(['texto fofo']);
});

it('sem teto do responsável o aluno de 16 anos vê todos os níveis', function () {
    criarFala('acerto', 'texto fofo', 'fofo');
    criarFala('acerto', 'texto sem filtro', 'sem');
    $aluno = alunoComTeto(16, null);

    $niveis = collect(range(1, 60))->map(fn () => app(FalaService::class)->sortear($aluno, ['acerto'], 'feliz')->texto)->unique();

    expect($niveis)->toContain('texto sem filtro');
});

it('a validação de preferências usa o teto efetivo', function () {
    $aluno = alunoComTeto(16, 'fofo', 'fofo');

    $this->actingAs($aluno)->patch('/conta/preferencias', ['zoeira' => 'sarc'])->assertSessionHasErrors('zoeira');
    $this->patch('/conta/preferencias', ['zoeira' => 'fofo'])->assertSessionHasNoErrors();
    expect(Nivel::from($aluno->fresh()->zoeira))->toBe(Nivel::Fofo);
});

it('o responsável baixa e libera o teto, e o shared prop acompanha', function () {
    $responsavel = User::factory()->responsavel()->create();
    $aluno = User::factory()->comIdade(16)->comResponsavel($responsavel)->create(['zoeira' => 'sem']);
    $this->actingAs($responsavel);

    $this->patch("/responsavel/alunos/{$aluno->id}/zoeira", ['teto' => 'fofo'])->assertSessionHasNoErrors();
    expect($aluno->fresh()->zoeira_responsavel)->toBe('fofo');
    $this->actingAs($aluno->fresh())->get('/inicio')->assertInertia(fn ($page) => $page
        ->where('auth.user.preferencias.zoeira', 'fofo')->where('auth.user.preferencias.zoeira_maxima', 'fofo'));

    $this->actingAs($responsavel)->patch("/responsavel/alunos/{$aluno->id}/zoeira", ['teto' => 'sem'])->assertSessionHasNoErrors();
    expect($aluno->fresh()->zoeira_responsavel)->toBeNull();
    $this->patch("/responsavel/alunos/{$aluno->id}/zoeira", ['teto' => 'invalido'])->assertSessionHasErrors('teto');
});
