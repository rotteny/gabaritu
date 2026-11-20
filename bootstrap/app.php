<?php

use App\Http\Middleware\ExigirAluno;
use App\Http\Middleware\ExigirAlunoPendente;
use App\Http\Middleware\ExigirContaDeAluno;
use App\Http\Middleware\ExigirResponsavel;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ModoEmBreve;
use App\Support\PaginaDeErro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Atrás do nginx de borda: só aceita X-Forwarded-* de quem estiver em TRUSTED_PROXIES (vazio = ninguém).
        // env() direto: a configuração de middleware é montada na construção do app, fora do config/.
        $proxies = env('TRUSTED_PROXIES'); // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
        $middleware->trustProxies(at: is_string($proxies) && $proxies !== '' ? $proxies : null);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('responsavel', 'responsavel/*') ? '/responsavel/entrar' : '/entrar');
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->caminhoInicial() ?? '/inicio');

        $middleware->alias([
            'aluno' => ExigirAluno::class,
            'aluno.pendente' => ExigirAlunoPendente::class,
            'aluno.conta' => ExigirContaDeAluno::class,
            'responsavel' => ExigirResponsavel::class,
        ]);

        $middleware->web(prepend: [
            ModoEmBreve::class,
        ], append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(fn (Response $response, Throwable $erro, Request $request) => PaginaDeErro::responder($response, $request));
    })->create();
