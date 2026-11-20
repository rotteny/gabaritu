<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\EstatisticasDeRespostas;
use App\Domain\Admin\Periodo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RespostasController extends Controller
{
    public function __invoke(Request $request, EstatisticasDeRespostas $respostas): Response
    {
        $periodo = Periodo::de($request->query('periodo'));

        return Inertia::render('admin/Respostas', [
            'periodo' => $periodo->chave(),
            'serie' => $respostas->serieDiaria($periodo),
            'materias' => $respostas->materiasMaisFeitas($periodo),
            'questionarios' => $respostas->questionariosMaisFeitos($periodo),
            'abandono' => $respostas->abandono($periodo),
            'etiquetas_de_erro' => $respostas->etiquetasDeErro($periodo),
        ]);
    }
}
