<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $regra = config('gabaritu.cadastro_aberto') ? 'Allow: /' : 'Disallow: /';

        return response("User-agent: *\n{$regra}\n", 200, ['Content-Type' => 'text/plain']);
    }
}
