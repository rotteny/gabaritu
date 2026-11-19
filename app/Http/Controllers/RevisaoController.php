<?php

namespace App\Http\Controllers;

use App\Domain\Tentativa\CriadorDeTentativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevisaoController extends Controller
{
    public function __invoke(Request $request, CriadorDeTentativa $criador): RedirectResponse
    {
        $tentativa = $criador->revisaoDoDia($request->user());

        if ($tentativa === null) {
            return redirect()->route('inicio')->with('vazio_revisao', true);
        }

        return redirect()->route('tentativas.show', $tentativa);
    }
}
