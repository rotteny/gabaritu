<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** O contrato das páginas usa `senha` e `lembrar`; o Fortify espera `password` e `remember`. */
class TraduzirCamposDeLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->is('entrar')) {
            $request->merge([
                'password' => $request->input('senha'),
                'remember' => $request->boolean('lembrar'),
            ]);
        }

        if ($request->isMethod('POST') && $request->is('redefinir-senha')) {
            $request->merge([
                'password' => $request->input('senha'),
                'password_confirmation' => $request->input('senha_confirmation'),
            ]);
        }

        return $next($request);
    }
}
