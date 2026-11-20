<?php

namespace App\Http\Controllers;

use App\Domain\Responsavel\PedidoDeVinculo;
use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** O aluno aceita ou recusa o pedido de um adulto para ser seu responsável. */
class ContaPedidosController extends Controller
{
    public function __construct(private readonly PedidoDeVinculo $pedidos) {}

    public function aceitar(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        $this->pedidos->aceitarComoAluno($this->doAluno($request, $convite), $request->user());

        return back();
    }

    public function recusar(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        $this->pedidos->recusar($this->doAluno($request, $convite));

        return back();
    }

    private function doAluno(Request $request, ConviteResponsavel $convite): ConviteResponsavel
    {
        abort_unless($convite->aluno_id === $request->user()->id && $convite->tipo === TipoConvite::PedidoVinculo->value && $convite->estaPendente(), 404);

        return $convite;
    }
}
