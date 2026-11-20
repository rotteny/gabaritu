<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Autenticador;
use App\Domain\Admin\EstadoDaSessao;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EntrarController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (Auth::guard('admin')->check() && (new EstadoDaSessao($request->session()))->doisFatoresCumprido()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('admin/Login', [
            'status' => $request->session()->get('status'),
            'acoes' => ['entrar' => route('admin.login.store', absolute: false)],
        ]);
    }

    public function store(Request $request, Autenticador $autenticador): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'string', 'max:255'], 'senha' => ['required', 'string', 'max:255']]);

        $admin = $autenticador->entrar($dados['email'], $dados['senha'], (string) $request->ip());
        (new EstadoDaSessao($request->session()))->iniciar($admin);

        return redirect()->route('admin.dois-fatores');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
