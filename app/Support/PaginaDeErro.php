<?php

namespace App\Support;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PaginaDeErro
{
    private const array STATUS_COM_PAGINA = [403, 404, 419, 429, 500, 503];

    private const array STATUS_DE_SERVIDOR = [500, 503];

    public static function responder(Response $resposta, Request $requisicao): Response
    {
        $status = $resposta->getStatusCode();

        if (! in_array($status, self::STATUS_COM_PAGINA, true) || self::deveMostrarDetalhes($status)) {
            return $resposta;
        }

        $usuario = $requisicao->hasSession() ? $requisicao->user() : null;
        $fala = app(FalaService::class)->sortear($usuario, ['erro_http:'.$status], Fala::HUMOR_NEUTRO);

        return Inertia::render('Erro', ['status' => $status, 'fala' => $fala])
            ->toResponse($requisicao)
            ->setStatusCode($status);
    }

    private static function deveMostrarDetalhes(int $status): bool
    {
        return in_array($status, self::STATUS_DE_SERVIDOR, true) && config('app.debug');
    }
}
