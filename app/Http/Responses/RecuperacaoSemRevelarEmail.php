<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

/**
 * Mesma resposta para e-mail cadastrado ou não: não revela quem tem conta.
 * Por isso ignora o status do broker, inclusive o RESET_THROTTLED: ele só acontece para e-mail que existe, então responder diferente entregaria a conta.
 */
class RecuperacaoSemRevelarEmail implements FailedPasswordResetLinkRequestResponse, Responsable
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['message' => trans('passwords.sent')])
            : back()->with('status', trans('passwords.sent'));
    }
}
