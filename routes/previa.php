<?php

/*
 * Prévia local das páginas do aluno: /_previa/{pagina} renderiza a página com as props de
 * resources/js/fixtures/previa/{pagina}.json. Só é carregada com APP_ENV=local (ver routes/web.php).
 */

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('_previa/{pagina}', function (string $pagina) {
    abort_unless(app()->isLocal(), 404);

    $arquivo = resource_path("js/fixtures/previa/{$pagina}.json");
    abort_unless(preg_match('/^[a-z0-9-]+$/', $pagina) === 1 && is_file($arquivo), 404);

    $fixture = json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);
    $compartilhado = json_decode((string) file_get_contents(resource_path('js/fixtures/previa/_compartilhado.json')), true, 512, JSON_THROW_ON_ERROR);

    $aluno = array_replace_recursive($compartilhado['auth']['user'], $fixture['aluno'] ?? []);
    if (request()->boolean('reduzir')) {
        $aluno['preferencias']['reduzir_movimento'] = true;
    }
    if (request()->has('mudo')) {
        $aluno['preferencias']['som'] = false;
    }

    return Inertia::render($fixture['component'], [
        ...$fixture['props'],
        'auth' => ['user' => ($fixture['anonimo'] ?? false) ? null : ($fixture['usuario'] ?? $aluno)],
        'gabaritu' => $compartilhado['gabaritu'],
    ]);
})->name('previa');
