<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * O Fortify só limita o login. Cadastro e recuperação de senha ficariam abertos a robô
 * (contas em massa, e-mail disparado para terceiros), então passam pelos limitadores nomeados.
 */
class LimitarFormulariosDeAcesso
{
    /** @var array<string, string> caminho => limitador */
    private const array LIMITADORES = [
        'cadastro' => 'cadastro',
        'esqueci-senha' => 'recuperacao-senha',
        'redefinir-senha' => 'recuperacao-senha',
    ];

    public function __construct(private readonly ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        $limitador = self::LIMITADORES[$request->path()] ?? null;

        if ($limitador === null || ! $request->isMethod('POST')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, $limitador);
    }
}
