<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('fechado (padrão)', function () {
    beforeEach(fn () => config(['gabaritu.cadastro_aberto' => false]));

    it('mostra a página em breve com o contato da config', function () {
        config(['gabaritu.email_contato' => 'contato@exemplo.test']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('EmBreve')
                ->where('emailContato', 'contato@exemplo.test'))
            ->assertSee('<meta name="robots" content="noindex">', false);
    });

    it('serve robots.txt com Disallow total', function () {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    });

    it('tira cadastro, login e área logada do ar', function (string $rota) {
        $this->get($rota)->assertNotFound();
    })->with(['/register', '/login', '/forgot-password', '/dashboard', '/settings/profile']);

    it('recusa o POST de cadastro', function () {
        $this->post('/register', ['name' => 'a', 'email' => 'a@a.test', 'password' => 'x'])->assertNotFound();
    });
});

describe('aberto', function () {
    beforeEach(fn () => config(['gabaritu.cadastro_aberto' => true]));

    it('volta ao comportamento do starter', function () {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome'))
            ->assertDontSee('noindex', false);
        $this->get('/register')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/robots.txt')->assertDontSee('Disallow: /');
    });
});
