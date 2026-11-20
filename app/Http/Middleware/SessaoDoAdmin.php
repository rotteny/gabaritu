<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Isola o painel: sob o prefixo do admin a sessão usa outro cookie (e outro caminho), com a vida curta do admin.
 * Assim o login do aluno e o do admin nunca se enxergam, se renovam ou se derrubam. Também marca tudo como noindex.
 * Precisa rodar antes do StartSession (prioridade em bootstrap/app.php).
 */
class SessaoDoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $prefixo = (string) config('gabaritu.admin_prefixo');

        if (! $request->is($prefixo, $prefixo.'/*')) {
            return $next($request);
        }

        $originais = config()->get(['session.cookie', 'session.path', 'session.lifetime', 'session.same_site']);
        config([
            'session.cookie' => config('session.cookie').'_admin',
            'session.path' => '/'.$prefixo,
            'session.lifetime' => (int) config('gabaritu.admin.inatividade_minutos'),
            'session.same_site' => 'strict',
        ]);

        try {
            $resposta = $next($request);
        } finally {
            config($originais);
        }

        $resposta->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $resposta->headers->set('Cache-Control', 'no-store, private');

        return $resposta;
    }
}
