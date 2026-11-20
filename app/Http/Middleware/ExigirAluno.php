<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rotas de aluno: o responsável recebe 404 e o aluno pendente vai para a tela de espera. */
class ExigirAluno
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return $next($request);
        }

        abort_if($usuario->ehResponsavel(), Response::HTTP_NOT_FOUND);

        if ($usuario->aguardaResponsavel()) {
            return redirect($usuario->caminhoInicial());
        }

        return $next($request);
    }
}
