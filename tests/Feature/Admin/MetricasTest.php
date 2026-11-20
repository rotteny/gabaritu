<?php

use App\Domain\Metricas\Metrica;
use App\Domain\Metricas\RegistradorDeMetricas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['gabaritu.cadastro_aberto' => true]);
    $this->hoje = now(config('gabaritu.fuso'))->format('Y-m-d');
    $this->metricas = fn () => DB::table('metricas_diarias')->where('dia', $this->hoje)->first();
});

it('conta visita só de visitante na landing', function () {
    $this->get('/');
    $this->get('/');
    $this->actingAs(User::factory()->comIdade(20)->create())->get('/');
    $this->get('/privacidade');

    expect((int) ($this->metricas)()->visitas_landing)->toBe(2);
});

it('conta login e ativo único por dia', function () {
    $aluno = User::factory()->comIdade(20)->create(['email' => 'a@exemplo.test']);

    foreach (range(1, 2) as $i) {
        $this->post('/entrar', ['email' => 'a@exemplo.test', 'senha' => 'password'])->assertRedirect();
        auth()->guard('web')->logout();
    }

    $linha = ($this->metricas)();
    expect((int) $linha->logins)->toBe(2)->and((int) $linha->alunos_ativos)->toBe(1);
});

it('não conta login de responsável como aluno ativo', function () {
    User::factory()->responsavel()->create(['email' => 'r@exemplo.test']);
    $this->post('/responsavel/entrar', ['email' => 'r@exemplo.test', 'senha' => 'password']);

    expect(($this->metricas)())->toBeNull();
});

it('conta resposta enviada sem guardar IP, user-agent nem id', function () {
    $conteudo = conteudoDeTeste(3);
    $aluno = User::factory()->comIdade(20)->create();
    $tentativa = iniciarTentativa($aluno, $conteudo['questionario']);

    responderAtual($aluno, $tentativa, true)->assertOk();
    responderAtual($aluno, $tentativa, false)->assertOk();

    $linha = ($this->metricas)();
    expect((int) $linha->respostas)->toBe(2)->and((int) $linha->alunos_ativos)->toBe(1)
        ->and(Schema::getColumnListing('metricas_diarias'))->toBe(['dia', 'alunos_ativos', 'logins', 'respostas', 'visitas_landing']);
});

it('incrementa de forma atômica no banco, sem ler antes', function () {
    $registrador = app(RegistradorDeMetricas::class);
    foreach (range(1, 5) as $i) {
        $registrador->incrementar(Metrica::Logins);
    }

    expect((int) ($this->metricas)()->logins)->toBe(5);
});

it('o token do ativo único não contém o id do aluno', function () {
    $aluno = User::factory()->comIdade(20)->create();
    app(RegistradorDeMetricas::class)->marcarAtivo($aluno);

    $chaves = collect((fn () => $this->storage)->call(Cache::getStore()))->keys()->filter(fn ($k) => str_contains($k, 'metricas:ativo'));
    expect($chaves)->toHaveCount(1)->and($chaves->first())->not->toEndWith(':'.$aluno->id);
});
