<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa o banco de teste e nunca o de desenvolvimento', function () {
    expect(config('database.connections.pgsql.database'))->toBe('gabaritu_test');
});

it('responde 200 na página inicial', function () {
    $this->get(route('home'))->assertOk();
});
