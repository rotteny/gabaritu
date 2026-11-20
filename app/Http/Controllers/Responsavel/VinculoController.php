<?php

namespace App\Http\Controllers\Responsavel;

use App\Domain\Responsavel\GestorDeVinculos;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VinculoController extends Controller
{
    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    public function destroy(Request $request, User $aluno): RedirectResponse
    {
        $this->authorize('acompanhar', $aluno);

        $this->vinculos->revogar($aluno, $request->user());

        return redirect('/responsavel');
    }
}
