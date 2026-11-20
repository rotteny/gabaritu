<?php

namespace App\Http\Controllers\Responsavel;

use App\Domain\Responsavel\GestorDeVinculos;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Depois que o aluno contesta um responsável, quem sobrou confirma a remoção ou restaura o vínculo. */
class VinculoSuspensoController extends Controller
{
    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    public function remover(Request $request, User $aluno, User $responsavel): RedirectResponse
    {
        $this->autorizar($request, $aluno, $responsavel);
        $this->vinculos->confirmarRemocao($aluno, $responsavel);

        return back();
    }

    public function restaurar(Request $request, User $aluno, User $responsavel): RedirectResponse
    {
        $this->autorizar($request, $aluno, $responsavel);
        $this->vinculos->restaurar($aluno, $responsavel);

        return back();
    }

    private function autorizar(Request $request, User $aluno, User $responsavel): void
    {
        $this->authorize('acompanhar', $aluno);
        abort_if($request->user()->is($responsavel), 404);
    }
}
