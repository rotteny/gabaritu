<?php

namespace App\Http\Controllers;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermosController extends Controller
{
    public function __invoke(Request $request, FalaService $falas): Response
    {
        return Inertia::render('Termos', [
            'versao' => (string) config('gabaritu.termos_versao'),
            'fala' => $falas->sortear($request->user(), ['termos'], Fala::HUMOR_NEUTRO),
        ]);
    }
}
