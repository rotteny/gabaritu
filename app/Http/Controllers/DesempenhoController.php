<?php

namespace App\Http\Controllers;

use App\Domain\Desempenho\DesempenhoDoAluno;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesempenhoController extends Controller
{
    public function __invoke(Request $request, DesempenhoDoAluno $desempenho, FalaService $falas): Response
    {
        $aluno = $request->user();
        $dados = $desempenho->montar($aluno);

        return Inertia::render('Desempenho', [
            ...$dados,
            'fala' => $falas->sortear($aluno, [$dados['materias'] === [] ? 'vazio:desempenho' : 'desempenho'], Fala::HUMOR_NEUTRO),
        ]);
    }
}
