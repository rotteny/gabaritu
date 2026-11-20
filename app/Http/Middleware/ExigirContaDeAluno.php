<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** /conta: o aluno pendente também entra (só para apagar a conta); o responsável (404) usa /responsavel/conta. */
class ExigirContaDeAluno
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_if($usuario !== null && $usuario->ehResponsavel(), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
