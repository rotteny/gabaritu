<?php

namespace App\Http\Controllers;

use App\Domain\Tentativa\ResponderQuestao;
use App\Models\Tentativa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RespostaController extends Controller
{
    public function store(Request $request, Tentativa $tentativa, ResponderQuestao $responder): JsonResponse
    {
        Gate::authorize('ver', $tentativa);

        $dados = $request->validate([
            'questao_id' => ['required', 'integer'],
            'alternativa_id' => ['required', 'integer'],
        ]);

        $resultado = $responder->executar(
            $request->user(),
            $tentativa,
            (int) $dados['questao_id'],
            (int) $dados['alternativa_id'],
            $request->session(),
        );

        $etiqueta = $resultado->correta ? null : $resultado->alternativaEscolhida?->etiqueta;

        return response()->json([
            'correta' => $resultado->correta,
            'alternativa_correta_id' => $resultado->alternativaCorreta->id,
            'etiqueta' => $etiqueta ? ['id' => $etiqueta->id, 'nome' => $etiqueta->nome] : null,
            'explicacao' => $resultado->explicacao,
            'fala' => $resultado->fala,
            'freio_ativo' => $resultado->freioAtivo,
            'chute' => $resultado->chute,
            'proxima' => $resultado->tentativaTerminou
                ? ['resultado_url' => route('tentativas.resultado', $tentativa, absolute: false)]
                : ['url' => route('tentativas.show', $tentativa, absolute: false)],
        ]);
    }
}
