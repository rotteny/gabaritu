<?php

namespace App\Http\Middleware;

use App\Domain\Admin\EstadoDaSessao;
use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Sem 2FA confirmado e cumprido nesta sessão, nenhuma rota do painel abre: vai para configurar ou desafiar. */
class AdminComDoisFatores
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        $estado = new EstadoDaSessao($request->session());

        if (! $admin instanceof Admin || ! $admin->temDoisFatoresConfirmado() || ! $estado->doisFatoresCumprido()) {
            return redirect()->route('admin.dois-fatores');
        }

        return $next($request);
    }
}
