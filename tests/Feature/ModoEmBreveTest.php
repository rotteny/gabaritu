<?php

describe('fechado (padrão)', function () {
    beforeEach(fn () => config(['gabaritu.cadastro_aberto' => false]));

    it('mostra a landing sem CTA de cadastro, com contato e repositório da config', function () {
        config([
            'gabaritu.email_contato' => 'contato@exemplo.test',
            'gabaritu.repositorio_url' => 'https://github.com/exemplo/repo',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->where('cadastroAberto', false)
                ->where('emailContato', 'contato@exemplo.test')
                ->where('repositorioUrl', 'https://github.com/exemplo/repo'))
            ->assertSee('<meta name="robots" content="noindex">', false);
    });

    it('serve robots.txt com Disallow total', function () {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    });

    it('tira cadastro, login e área logada do ar', function (string $rota) {
        $this->get($rota)->assertNotFound();
    })->with(['/cadastro', '/entrar', '/esqueci-senha', '/inicio', '/conta']);

    it('recusa o POST de cadastro', function () {
        $this->post('/cadastro', ['apelido' => 'a', 'email' => 'a@a.test', 'senha' => 'x'])->assertNotFound();
    });
});

describe('aberto', function () {
    beforeEach(fn () => config(['gabaritu.cadastro_aberto' => true]));

    it('mostra a landing com os botões de criar conta e entrar', function () {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->where('cadastroAberto', true))
            ->assertDontSee('noindex', false);
        $this->get('/cadastro')->assertOk();
        $this->get('/entrar')->assertOk();
        $this->get('/robots.txt')->assertDontSee('Disallow: /');
    });
});

describe('quem somos', function () {
    it('é pública com o cadastro fechado e com ele aberto', function (bool $aberto) {
        config(['gabaritu.cadastro_aberto' => $aberto]);

        $this->get('/quem-somos')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('QuemSomos'));
    })->with([false, true]);
});

it('compartilha cadastro_aberto pra esconder Entrar na área pública fechada', function (bool $aberto) {
    config(['gabaritu.cadastro_aberto' => $aberto]);

    $this->get('/quem-somos')->assertInertia(fn ($page) => $page->where('gabaritu.cadastro_aberto', $aberto));
})->with([false, true]);
