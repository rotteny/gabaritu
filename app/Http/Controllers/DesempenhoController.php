<?php

namespace App\Http\Controllers;

use App\Domain\Desempenho\PainelDeDesempenho;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Revisao\FilaDeRevisao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesempenhoController extends Controller
{
    public function __invoke(Request $request, PainelDeDesempenho $painel, FalaService $falas, FilaDeRevisao $fila): Response
    {
        $aluno = $request->user();
        $materias = $painel->montar($aluno);

        return Inertia::render('Desempenho', [
            'materias' => $materias,
            'revisao' => [
                'hoje' => min($fila->vencidas($aluno)->count(), $fila->vagasHoje($aluno)),
                'amanha' => $fila->esperandoAmanha($aluno),
            ],
            'fala' => $falas->sortear($aluno, [$materias === [] ? 'vazio:desempenho' : 'desempenho'], Fala::HUMOR_NEUTRO),
        ]);
    }
}
