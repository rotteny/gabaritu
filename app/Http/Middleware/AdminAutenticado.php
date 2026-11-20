<?php

namespace App\Http\Middleware;

use App\Domain\Admin\EstadoDaSessao;
use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Barra quem não está logado como admin e derruba a sessão parada por mais de 30 minutos ou com senha trocada. */
class AdminAutenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');
        $admin = $guard->user();
        $estado = new EstadoDaSessao($request->session());

        if (! $admin instanceof Admin) {
            return redirect()->route('admin.login');
        }

        if ($estado->inativo() || $estado->senhaMudou($admin)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('status', 'sessao_expirada');
        }

        $estado->registrarAtividade();

        return $next($request);
    }
}
