<?php

namespace App\Http\Controllers;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacidadeController extends Controller
{
    public function __invoke(Request $request, FalaService $falas): Response
    {
        return Inertia::render('Privacidade', [
            'fala' => $falas->sortear($request->user(), ['privacidade'], Fala::HUMOR_NEUTRO),
        ]);
    }
}
