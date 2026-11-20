<?php

namespace App\Http\Controllers\Responsavel;

use App\Enums\Nivel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ZoeiraController extends Controller
{
    public function update(Request $request, User $aluno): RedirectResponse
    {
        $this->authorize('acompanhar', $aluno);
        $dados = $request->validate(['teto' => ['required', Rule::enum(Nivel::class)]]);

        $aluno->forceFill(['zoeira_responsavel' => $dados['teto'] === Nivel::Sem->value ? null : $dados['teto']])->save();

        return back();
    }
}
