<?php

namespace App\Http\Controllers\Responsavel;

use App\Concerns\PasswordValidationRules;
use App\Domain\Responsavel\GestorDeVinculos;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlunoController extends Controller
{
    use PasswordValidationRules;

    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    public function destroy(Request $request, User $aluno): RedirectResponse
    {
        $this->authorize('acompanhar', $aluno);
        $request->validate(['senha' => $this->currentPasswordRules()]);

        $this->vinculos->apagarAluno($aluno);

        return redirect('/responsavel');
    }
}
