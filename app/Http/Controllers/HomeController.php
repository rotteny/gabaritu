<?php

namespace App\Http\Controllers;

use App\Domain\Metricas\RegistradorDeMetricas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, RegistradorDeMetricas $metricas): Response
    {
        if ($request->isMethod('GET') && $request->user() === null) {
            $metricas->visitaNaLanding();
        }

        return Inertia::render('Landing', [
            'cadastroAberto' => (bool) config('gabaritu.cadastro_aberto'),
            'emailContato' => config('gabaritu.email_contato'),
            'repositorioUrl' => config('gabaritu.repositorio_url'),
        ]);
    }
}
