<?php

use App\Http\Controllers\ContaController;
use App\Http\Controllers\DesempenhoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PrivacidadeController;
use App\Http\Controllers\RespostaController;
use App\Http\Controllers\RevisaoController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\TentativaController;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', RobotsController::class);

Route::get('/', HomeController::class)->name('home');
Route::get('privacidade', PrivacidadeController::class)->name('privacidade');

Route::middleware('auth')->group(function () {
    Route::get('inicio', InicioController::class)->name('inicio');
    Route::get('materias/{materia:slug}', MateriaController::class)->name('materias.show');
    Route::get('revisao', RevisaoController::class)->name('revisao');
    Route::get('desempenho', DesempenhoController::class)->name('desempenho');

    Route::post('questionarios/{questionario}/tentativas', [TentativaController::class, 'store'])->name('tentativas.store');
    Route::get('tentativas/{tentativa}', [TentativaController::class, 'show'])->name('tentativas.show');
    Route::get('tentativas/{tentativa}/resultado', [TentativaController::class, 'resultado'])->name('tentativas.resultado');
    Route::post('tentativas/{tentativa}/revisao', [TentativaController::class, 'revisarErradas'])->name('tentativas.revisar-erradas');
    Route::post('tentativas/{tentativa}/respostas', [RespostaController::class, 'store'])
        ->middleware('throttle:120,1')->name('respostas.store');

    Route::get('conta', [ContaController::class, 'show'])->name('conta');
    Route::patch('conta/preferencias', [ContaController::class, 'preferencias'])->name('conta.preferencias');
    Route::patch('conta/perfil', [ContaController::class, 'perfil'])->name('conta.perfil');
    Route::put('conta/senha', [ContaController::class, 'senha'])->middleware('throttle:6,1')->name('conta.senha');
    Route::delete('conta', [ContaController::class, 'destroy'])->middleware('throttle:6,1')->name('conta.destroy');
});

// Fallback no grupo web: sem ele o 404 de URL inexistente nasce sem sessão e o aluno aparece deslogado.
Route::fallback(fn () => abort(404));

if (app()->isLocal()) {
    require __DIR__.'/previa.php';
}
