<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tela de espera: só o aluno que ainda aguarda o responsável. */
class ExigirAlunoPendente
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_if($usuario !== null && $usuario->ehResponsavel(), Response::HTTP_NOT_FOUND);

        if ($usuario !== null && ! $usuario->aguardaResponsavel()) {
            return redirect($usuario->caminhoInicial());
        }

        return $next($request);
    }
}
