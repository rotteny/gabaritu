<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModoEmBreve
{
    private const array ROTAS_PUBLICAS = ['/', 'robots.txt', 'quem-somos'];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('gabaritu.cadastro_aberto') || $request->is(self::ROTAS_PUBLICAS)) {
            return $next($request);
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}
