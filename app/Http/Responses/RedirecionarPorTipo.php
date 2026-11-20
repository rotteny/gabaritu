<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Symfony\Component\HttpFoundation\Response;

/** Depois de entrar ou se cadastrar: responsável no painel, aluno pendente na espera, aluno ativo no início. */
class RedirecionarPorTipo implements LoginResponse, RegisterResponse
{
    /** @param  Request  $request */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        }

        $usuario = $request->user();

        return $usuario->aguardaResponsavel() ? redirect($usuario->caminhoInicial()) : redirect()->intended($usuario->caminhoInicial());
    }
}
