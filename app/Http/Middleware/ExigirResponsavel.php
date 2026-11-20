<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rotas de responsável: o aluno recebe 404. */
class ExigirResponsavel
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_if($usuario !== null && ! $usuario->ehResponsavel(), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
