<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        if (config('gabaritu.cadastro_aberto')) {
            return Inertia::render('Welcome');
        }

        return Inertia::render('EmBreve', [
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
