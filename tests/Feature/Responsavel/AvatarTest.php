<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(fn () => Mail::fake());

it('a lista de avatares é a fixa de bichos marinhos', function () {
    expect(config('gabaritu.avatares'))->toBe([
        'peixe-palhaco', 'caranguejo', 'polvo', 'tartaruga', 'agua-viva', 'cavalo-marinho',
        'estrela-do-mar', 'golfinho', 'baleia', 'lula', 'ourico', 'lagosta',
    ]);
});

it('o cadastro aceita o avatar escolhido', function () {
    $this->post('/cadastro', dadosDeCadastro(['avatar' => 'polvo']))->assertSessionHasNoErrors();

    expect(User::firstOrFail()->avatar)->toBe('polvo');
});

it('o cadastro sorteia um avatar da lista quando não vem nenhum', function () {
    $this->post('/cadastro', dadosDeCadastro())->assertSessionHasNoErrors();

    expect(User::firstOrFail()->avatar)->toBeIn(config('gabaritu.avatares'));
});

it('o cadastro recusa avatar fora da lista', function () {
    $this->post('/cadastro', dadosDeCadastro(['avatar' => 'tubarao-martelo']))->assertSessionHasErrors('avatar');

    expect(User::count())->toBe(0);
});

it('PATCH /conta/perfil troca o avatar e recusa valor inválido com 422', function () {
    $aluno = User::factory()->create(['avatar' => 'polvo']);
    $this->actingAs($aluno);

    $this->patch('/conta/perfil', ['apelido' => 'Novo', 'serie' => '2em', 'avatar' => 'baleia'])->assertSessionHasNoErrors();
    expect($aluno->fresh()->avatar)->toBe('baleia');

    $this->patchJson('/conta/perfil', ['apelido' => 'Novo', 'serie' => '2em', 'avatar' => 'foto.png'])->assertStatus(422)->assertJsonValidationErrors('avatar');
    $this->patch('/conta/perfil', ['apelido' => 'Novo', 'serie' => '2em'])->assertSessionHasNoErrors();
    expect($aluno->fresh()->avatar)->toBe('baleia');
});

it('expõe o avatar em auth.user, na Conta, no painel e na página do aluno', function () {
    $responsavel = User::factory()->responsavel()->create();
    $aluno = User::factory()->comResponsavel($responsavel)->create(['avatar' => 'lula']);

    $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page
        ->where('auth.user.avatar', 'lula')->where('perfil.avatar', 'lula')->has('avatares', 12));
    $this->actingAs($responsavel)->get('/responsavel')->assertInertia(fn ($page) => $page->where('alunos.0.avatar', 'lula'));
    $this->get("/responsavel/alunos/{$aluno->id}")->assertInertia(fn ($page) => $page->where('aluno.avatar', 'lula'));
});

it('não há coluna nem rota de upload de foto', function () {
    expect(Schema::getColumnListing('users'))->not->toContain('foto')->not->toContain('avatar_url');
    $this->actingAs(User::factory()->create())->post('/conta/foto', [])->assertStatus(405);
});
