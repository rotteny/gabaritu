<?php

use App\Models\User;

beforeEach(fn () => app()->setLocale('pt_BR'));

it('recusa login com mensagem em português', function () {
    User::factory()->create(['email' => 'a@b.test']);

    $this->from('/entrar')->post('/entrar', ['email' => 'a@b.test', 'senha' => 'errada'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    expect(__('auth.failed'))->toBe('E-mail ou senha incorretos.');
});

it('traduz o erro de zoeira inválida em /conta/preferencias', function () {
    $this->actingAs(User::factory()->create())
        ->patch('/conta/preferencias', ['zoeira' => 'inexistente'])
        ->assertSessionHasErrors(['zoeira' => 'Opção inválida.']);
});

it('traduz os nomes dos atributos e o throttle', function () {
    $nomes = trans('validation.attributes');

    expect($nomes)->toHaveKeys(['zoeira', 'apelido', 'serie', 'senha', 'ano_nascimento'])
        ->and(__('auth.throttle', ['seconds' => 5]))->toContain('5 segundos');
});
