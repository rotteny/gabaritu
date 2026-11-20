<?php

namespace App\Http\Controllers\Responsavel;

use App\Enums\TipoConta;
use App\Http\Controllers\Controller;
use App\Support\RedirectInterno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Login do responsável: só aceita conta `tipo=responsavel`; credencial de aluno cai na mesma mensagem genérica. */
class EntrarController extends Controller
{
    public function show(Request $request): Response
    {
        RedirectInterno::guardar($request);

        return Inertia::render('responsavel/Entrar', ['status' => $request->session()->get('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'string'], 'senha' => ['required', 'string']]);

        $entrou = Auth::guard('web')->attempt([
            'email' => mb_strtolower(trim($dados['email'])),
            'password' => $dados['senha'],
            'tipo' => TipoConta::Responsavel->value,
        ], $request->boolean('lembrar'));

        if (! $entrou) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/responsavel');
    }
}
