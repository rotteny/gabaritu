<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
| Painel admin (docs/admin.md). Carregado por routes/web.php sob o prefixo de gabaritu.admin_prefixo, com nomes `admin.*`.
| Camadas: login → (admin.autenticado) sessão válida → (admin.dois-fatores) 2FA confirmado e cumprido nesta sessão.
*/
Route::get('entrar', [Admin\EntrarController::class, 'show'])->name('login');
Route::post('entrar', [Admin\EntrarController::class, 'store'])->name('login.store');

Route::middleware('admin.autenticado')->group(function () {
    Route::post('sair', [Admin\EntrarController::class, 'destroy'])->name('logout');

    Route::get('dois-fatores', [Admin\DoisFatoresController::class, 'show'])->name('dois-fatores');
    Route::post('dois-fatores/confirmar', [Admin\DoisFatoresController::class, 'confirmar'])->name('dois-fatores.confirmar');
    Route::post('dois-fatores/desafio', [Admin\DoisFatoresController::class, 'desafiar'])->name('dois-fatores.desafiar');

    Route::middleware('admin.dois-fatores')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('alunos', [Admin\AlunosController::class, 'index'])->name('alunos');
        Route::get('alunos/{aluno}', [Admin\AlunosController::class, 'show'])->whereNumber('aluno')->name('alunos.show');
        Route::post('alunos/{aluno}/revelar-email', [Admin\RevelarEmailController::class, 'aluno'])->whereNumber('aluno')->name('alunos.revelar-email');

        Route::get('responsaveis', [Admin\ResponsaveisController::class, 'index'])->name('responsaveis');
        Route::get('responsaveis/{responsavel}', [Admin\ResponsaveisController::class, 'show'])->whereNumber('responsavel')->name('responsaveis.show');
        Route::post('responsaveis/{responsavel}/revelar-email', [Admin\RevelarEmailController::class, 'responsavel'])->whereNumber('responsavel')->name('responsaveis.revelar-email');

        Route::get('questoes', [Admin\QuestoesController::class, 'index'])->name('questoes');
        Route::get('questoes/{questao}', [Admin\QuestoesController::class, 'show'])->whereNumber('questao')->name('questoes.show');
        Route::patch('questoes/{questao}/status-juridico', Admin\StatusJuridicoController::class)->whereNumber('questao')->name('questoes.status-juridico');

        Route::get('respostas', Admin\RespostasController::class)->name('respostas');
    });
});
