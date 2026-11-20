<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\PainelDeNumeros;
use App\Domain\Admin\Periodo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PainelDeNumeros $painel): Response
    {
        $periodo = Periodo::de($request->query('periodo'));

        return Inertia::render('admin/Dashboard', [
            'periodo' => $periodo->chave(),
            'cartoes' => $painel->cartoes(),
            'funil' => $painel->funil($periodo),
            'series' => $painel->series($periodo),
        ]);
    }
}
