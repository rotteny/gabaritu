<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Landing', [
            'cadastroAberto' => (bool) config('gabaritu.cadastro_aberto'),
            'emailContato' => config('gabaritu.email_contato'),
            'repositorioUrl' => config('gabaritu.repositorio_url'),
        ]);
    }
}
