<?php

use App\Http\Controllers\AguardandoResponsavelController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\ContaPedidosController;
use App\Http\Controllers\ContaResponsaveisController;
use App\Http\Controllers\DesempenhoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PrivacidadeController;
use App\Http\Controllers\QuemSomosController;
use App\Http\Controllers\Responsavel;
use App\Http\Controllers\RespostaController;
use App\Http\Controllers\RevisaoController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\TentativaController;
use App\Http\Controllers\TermosController;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', RobotsController::class);

Route::get('/', HomeController::class)->name('home');
Route::get('privacidade', PrivacidadeController::class)->name('privacidade');
Route::get('quem-somos', QuemSomosController::class)->name('quem-somos');
Route::get('termos', TermosController::class)->name('termos');

Route::middleware(['auth', 'aluno'])->group(function () {
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

    Route::patch('conta/preferencias', [ContaController::class, 'preferencias'])->name('conta.preferencias');
    Route::patch('conta/perfil', [ContaController::class, 'perfil'])->name('conta.perfil');
    Route::put('conta/senha', [ContaController::class, 'senha'])->middleware('throttle:6,1')->name('conta.senha');
    Route::post('conta/responsaveis', [ContaResponsaveisController::class, 'store'])->middleware('throttle:convite-emissao')->name('conta.responsaveis.store');
    Route::post('conta/responsaveis/{responsavel}/contestar', [ContaResponsaveisController::class, 'contestar'])->name('conta.responsaveis.contestar');
    Route::post('conta/acompanhamento/encerrar', [ContaController::class, 'encerrarAcompanhamento'])->middleware('throttle:6,1')->name('conta.acompanhamento.encerrar');
    Route::delete('conta/responsaveis/convites/{convite}', [ContaResponsaveisController::class, 'destroy'])->name('conta.responsaveis.convites.destroy');
});

// /conta: o aluno pendente entra (só para apagar a conta e sair).
Route::middleware(['auth', 'aluno.conta'])->group(function () {
    Route::get('conta', [ContaController::class, 'show'])->name('conta');
    Route::delete('conta', [ContaController::class, 'destroy'])->middleware('throttle:6,1')->name('conta.destroy');
    Route::post('conta/pedidos/{convite}/aceitar', [ContaPedidosController::class, 'aceitar'])->name('conta.pedidos.aceitar');
    Route::post('conta/pedidos/{convite}/recusar', [ContaPedidosController::class, 'recusar'])->name('conta.pedidos.recusar');
});

Route::middleware(['auth', 'aluno.pendente'])->prefix('aguardando-responsavel')->group(function () {
    Route::get('/', [AguardandoResponsavelController::class, 'show'])->name('aguardando-responsavel');
    Route::post('reenviar', [AguardandoResponsavelController::class, 'reenviar'])->middleware('throttle:convite-emissao')->name('aguardando-responsavel.reenviar');
    Route::patch('email', [AguardandoResponsavelController::class, 'atualizarEmail'])->middleware('throttle:convite-emissao')->name('aguardando-responsavel.email');
});

// Convite: público (o token é a credencial). Limite por IP e por token.
Route::prefix('responsavel/convite/{token}')->middleware('throttle:convite-aceite')->group(function () {
    Route::get('/', [Responsavel\ConviteRecebidoController::class, 'show'])->name('responsavel.convite');
    Route::post('/', [Responsavel\ConviteRecebidoController::class, 'aceitar'])->name('responsavel.convite.aceitar');
    Route::post('recusar', [Responsavel\ConviteRecebidoController::class, 'recusar'])->name('responsavel.convite.recusar');
});

// Acesso do responsável separado do aluno: login e recuperação de senha próprios.
Route::prefix('responsavel')->middleware('guest')->group(function () {
    Route::get('entrar', [Responsavel\EntrarController::class, 'show'])->name('responsavel.entrar');
    Route::post('entrar', [Responsavel\EntrarController::class, 'store'])->middleware('throttle:login');
    Route::get('esqueci-senha', [Responsavel\RecuperacaoDeSenhaController::class, 'esqueci'])->name('responsavel.esqueci-senha');
    Route::post('esqueci-senha', [Responsavel\RecuperacaoDeSenhaController::class, 'enviar'])->middleware('throttle:recuperacao-senha');
    Route::get('redefinir-senha/{token}', [Responsavel\RecuperacaoDeSenhaController::class, 'formulario'])->name('responsavel.redefinir-senha');
    Route::post('redefinir-senha', [Responsavel\RecuperacaoDeSenhaController::class, 'redefinir'])->middleware('throttle:recuperacao-senha');
});

Route::middleware(['auth', 'responsavel'])->prefix('responsavel')->name('responsavel.')->group(function () {
    Route::get('/', [Responsavel\PainelController::class, 'index'])->name('painel');
    Route::get('conta', [Responsavel\ContaController::class, 'show'])->name('conta');
    Route::put('conta/senha', [Responsavel\ContaController::class, 'senha'])->middleware('throttle:6,1')->name('conta.senha');
    Route::delete('conta', [Responsavel\ContaController::class, 'destroy'])->middleware('throttle:6,1')->name('conta.destroy');

    Route::post('pedidos', [Responsavel\PedidoController::class, 'store'])->middleware('throttle:convite-emissao')->name('pedidos.store');
    Route::post('pedidos/{convite}/aprovar', [Responsavel\PedidoController::class, 'aprovar'])->name('pedidos.aprovar');
    Route::post('pedidos/{convite}/recusar', [Responsavel\PedidoController::class, 'recusar'])->name('pedidos.recusar');
    Route::delete('pedidos/{convite}', [Responsavel\PedidoController::class, 'destroy'])->name('pedidos.destroy');
    Route::delete('alunos/{aluno}/suspensos/{responsavel}', [Responsavel\VinculoSuspensoController::class, 'remover'])->name('alunos.suspensos.remover');
    Route::post('alunos/{aluno}/suspensos/{responsavel}/restaurar', [Responsavel\VinculoSuspensoController::class, 'restaurar'])->name('alunos.suspensos.restaurar');

    Route::get('alunos/{aluno}', [Responsavel\PainelController::class, 'show'])->name('alunos.show');
    Route::patch('alunos/{aluno}/zoeira', [Responsavel\ZoeiraController::class, 'update'])->name('alunos.zoeira');
    Route::post('alunos/{aluno}/convites', [Responsavel\ConviteEnviadoController::class, 'store'])->middleware('throttle:convite-emissao')->name('alunos.convites');
    Route::delete('alunos/{aluno}/convites/{convite}', [Responsavel\ConviteEnviadoController::class, 'destroy'])->name('alunos.convites.destroy');
    Route::delete('alunos/{aluno}/vinculo', [Responsavel\VinculoController::class, 'destroy'])->name('alunos.vinculo');
    Route::delete('alunos/{aluno}', [Responsavel\AlunoController::class, 'destroy'])->middleware('throttle:6,1')->name('alunos.destroy');
});

// Fallback no grupo web: sem ele o 404 de URL inexistente nasce sem sessão e o aluno aparece deslogado.
Route::fallback(fn () => abort(404));

if (app()->isLocal()) {
    require __DIR__.'/previa.php';
}
