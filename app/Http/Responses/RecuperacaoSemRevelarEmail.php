<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

/** Mesma resposta para e-mail cadastrado ou não: não revela quem tem conta. */
class RecuperacaoSemRevelarEmail implements FailedPasswordResetLinkRequestResponse, Responsable
{
    public function __construct(private readonly string $status) {}

    public function toResponse($request)
    {
        if ($this->status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => trans($this->status)]);
        }

        return $request->wantsJson()
            ? response()->json(['message' => trans('passwords.sent')])
            : back()->with('status', trans('passwords.sent'));
    }
}
