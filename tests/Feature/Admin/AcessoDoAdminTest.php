<?php

use App\Domain\Admin\DoisFatores;
use App\Models\Admin;
use App\Models\AdminAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => config(['gabaritu.cadastro_aberto' => true]));

it('manda quem não está logado para o login do admin', function (string $rota) {
    $this->get($rota)->assertRedirect('/admin/entrar');
})->with(['/admin', '/admin/alunos', '/admin/responsaveis', '/admin/questoes', '/admin/respostas']);

it('barra tudo sem 2FA confirmado e mostra o QR para configurar', function () {
    Admin::create(['email' => 'dono@gabaritu.test', 'password' => 'Senha-do-admin-123']);

    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123'])->assertRedirect('/admin/dois-fatores');
    $this->get('/admin')->assertRedirect('/admin/dois-fatores');
    $this->get('/admin/alunos')->assertRedirect('/admin/dois-fatores');
    $this->get('/admin/dois-fatores')->assertOk()->assertInertia(fn ($p) => $p
        ->component('admin/DoisFatores')->where('modo', 'configurar')
        ->where('qr_svg', fn ($svg) => str_contains($svg, '<svg'))->has('segredo'));
});

it('confirma o 2FA, mostra 8 códigos uma vez e guarda só o hash', function () {
    $admin = Admin::create(['email' => 'dono@gabaritu.test', 'password' => 'Senha-do-admin-123']);
    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->get('/admin/dois-fatores');
    $segredo = $admin->fresh()->dois_fatores_segredo;

    $this->post('/admin/dois-fatores/confirmar', ['codigo' => '000000'])->assertSessionHasErrors('codigo');
    expect($admin->fresh()->temDoisFatoresConfirmado())->toBeFalse();

    $this->post('/admin/dois-fatores/confirmar', ['codigo' => codigoTotp($segredo)])->assertRedirect('/admin/dois-fatores');
    $this->get('/admin/dois-fatores')->assertInertia(function ($p) use ($admin) {
        $p->where('modo', 'codigos')->has('codigos', 8);
        $guardados = $admin->fresh()->codigos_recuperacao;
        expect($guardados)->toHaveCount(8)->and(in_array($p->toArray()['props']['codigos'][0], $guardados, true))->toBeFalse();
    });
    $this->get('/admin')->assertOk();
    expect($admin->fresh()->temDoisFatoresConfirmado())->toBeTrue();
});

it('recusa código TOTP errado no desafio e aceita o certo', function () {
    [, $segredo] = adminComDoisFatores();
    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);

    $this->get('/admin')->assertRedirect('/admin/dois-fatores');
    $this->post('/admin/dois-fatores/desafio', ['codigo' => '123456'])->assertSessionHasErrors('codigo');
    $this->get('/admin')->assertRedirect('/admin/dois-fatores');

    $this->post('/admin/dois-fatores/desafio', ['codigo' => codigoTotp($segredo)])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertInertia(fn ($p) => $p->component('admin/Dashboard'));
});

it('não aceita o mesmo código TOTP duas vezes', function () {
    [, $segredo] = adminComDoisFatores();
    $codigo = codigoTotp($segredo);

    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['codigo' => $codigo])->assertRedirect('/admin');
    $this->post('/admin/sair');

    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['codigo' => $codigo])->assertSessionHasErrors('codigo');
});

it('usa código de recuperação uma vez só', function () {
    [$admin, $segredo] = adminComDoisFatores();
    $codigos = app(DoisFatores::class)->gerarCodigosDeRecuperacao($admin);

    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['recuperacao' => $codigos[0]])->assertRedirect('/admin');
    expect($admin->fresh()->codigos_recuperacao)->toHaveCount(7);
    $this->post('/admin/sair');

    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['recuperacao' => $codigos[0]])->assertSessionHasErrors('recuperacao');
    $this->post('/admin/dois-fatores/desafio', ['recuperacao' => strtoupper($codigos[1])])->assertRedirect('/admin');
});

it('limita o login a 5 tentativas por minuto e não diz se o e-mail existe', function () {
    adminComDoisFatores();

    foreach (range(1, 5) as $i) {
        $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'errada'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }
    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123'])
        ->assertSessionHasErrors(['email' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
});

it('responde igual para e-mail que não existe', function () {
    $this->post('/admin/entrar', ['email' => 'ninguem@x.test', 'senha' => 'x'])->assertSessionHasErrors(['email' => trans('auth.failed')]);
});

it('limita tentativas de código do desafio', function () {
    adminComDoisFatores();
    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);

    foreach (range(1, 5) as $i) {
        $this->post('/admin/dois-fatores/desafio', ['codigo' => '111111'])->assertSessionHasErrors('codigo');
    }
    $this->post('/admin/dois-fatores/desafio', ['codigo' => '111111'])->assertSessionHasErrors(['codigo' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
});

it('não deixa aluno logado entrar no admin, nem o admin no site do aluno', function () {
    $aluno = User::factory()->comIdade(20)->create();

    $this->actingAs($aluno)->get('/admin')->assertRedirect('/admin/entrar');
    $this->actingAs($aluno)->get('/admin/alunos')->assertRedirect('/admin/entrar');
    $this->actingAs($aluno)->get('/inicio')->assertOk();

    [$admin] = adminComDoisFatores();
    comoAdmin($admin);
    $this->get('/admin')->assertOk();
});

it('usa cookie e caminho de sessão próprios sob o prefixo do admin e marca noindex', function () {
    $base = config('session.cookie');
    $resposta = $this->get('/admin/entrar')->assertOk();

    $resposta->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $resposta->assertCookie($base.'_admin');
    $resposta->assertCookieMissing($base);
    expect($resposta->getCookie($base.'_admin', false)->getPath())->toBe('/admin');

    expect(config('session.cookie'))->toBe($base);
    $this->refreshApplication();
    $this->get('/entrar')->assertCookie($base)->assertCookieMissing($base.'_admin');
});

it('derruba a sessão parada por mais de 30 minutos', function () {
    [$admin] = adminComDoisFatores();
    comoAdmin($admin);
    $this->get('/admin')->assertOk();

    $this->travel(31)->minutes();
    $this->get('/admin')->assertRedirect('/admin/entrar');
    $this->get('/admin/entrar')->assertInertia(fn ($p) => $p->where('status', 'sessao_expirada'));
});

it('derruba a sessão quando a senha é trocada', function () {
    [$admin] = adminComDoisFatores();
    comoAdmin($admin);
    $this->get('/admin')->assertOk();

    $admin->forceFill(['password' => 'Outra-senha-forte-456'])->save();

    $this->get('/admin')->assertRedirect('/admin/entrar');
});

it('funciona mesmo com o cadastro fechado', function () {
    config(['gabaritu.cadastro_aberto' => false]);

    $this->get('/admin/entrar')->assertOk();
});

it('audita o login', function () {
    [, $segredo] = adminComDoisFatores();
    $this->post('/admin/entrar', ['email' => 'dono@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['codigo' => codigoTotp($segredo)]);

    expect(AdminAuditoria::query()->where('acao', 'login')->count())->toBe(1);
});

it('não guarda a senha em claro', function () {
    [$admin] = adminComDoisFatores();

    expect(Hash::check('Senha-do-admin-123', $admin->password))->toBeTrue();
});
