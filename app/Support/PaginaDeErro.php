<?php

namespace App\Support;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PaginaDeErro
{
    private const array STATUS_COM_PAGINA = [403, 404, 419, 429, 500, 503];

    /** O painel admin é sóbrio: erro dele não usa o Peixoto nem a conta do aluno. */
    private static function ehDoAdmin(Request $requisicao): bool
    {
        $prefixo = (string) config('gabaritu.admin_prefixo');

        return $requisicao->is($prefixo, $prefixo.'/*');
    }

    private const array STATUS_DE_SERVIDOR = [500, 503];

    public static function responder(Response $resposta, Request $requisicao): Response
    {
        $status = $resposta->getStatusCode();

        if (! in_array($status, self::STATUS_COM_PAGINA, true) || self::deveMostrarDetalhes($status) || self::ehDoAdmin($requisicao)) {
            return $resposta;
        }

        $usuario = $requisicao->hasSession() ? $requisicao->user() : null;
        $fala = app(FalaService::class)->sortear($usuario, ['erro_http:'.$status], Fala::HUMOR_NEUTRO);

        return Inertia::render('Erro', ['status' => $status, 'fala' => $fala])
            ->with(app(HandleInertiaRequests::class)->share($requisicao))
            ->toResponse($requisicao)
            ->setStatusCode($status);
    }

    private static function deveMostrarDetalhes(int $status): bool
    {
        return in_array($status, self::STATUS_DE_SERVIDOR, true) && config('app.debug');
    }
}
