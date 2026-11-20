<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModoEmBreve
{
    private const array ROTAS_PUBLICAS = ['/', 'robots.txt', 'quem-somos', 'privacidade', 'termos'];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('gabaritu.cadastro_aberto') || $request->is(self::ROTAS_PUBLICAS) || $this->ehDoAdmin($request)) {
            return $next($request);
        }

        abort(Response::HTTP_NOT_FOUND);
    }

    /** O painel funciona mesmo com o cadastro fechado: ele tem login e 2FA próprios. */
    private function ehDoAdmin(Request $request): bool
    {
        $prefixo = (string) config('gabaritu.admin_prefixo');

        return $request->is($prefixo, $prefixo.'/*');
    }
}
