<?php

namespace App\Http\Controllers\Responsavel;

use App\Concerns\PasswordValidationRules;
use App\Domain\Responsavel\GestorDeVinculos;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContaController extends Controller
{
    use PasswordValidationRules;

    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    public function show(Request $request): Response
    {
        $responsavel = $request->user();

        return Inertia::render('responsavel/Conta', [
            'responsavel' => [
                'nome' => $responsavel->apelido,
                'email' => $responsavel->email,
                'ano_nascimento' => $responsavel->ano_nascimento,
            ],
            'alunos_unicos' => $responsavel->alunos()->get()
                ->filter(fn (User $aluno) => $aluno->vinculosAtivosComResponsaveis()->count() <= 1)
                ->pluck('apelido')->values()->all(),
        ]);
    }

    public function senha(Request $request): RedirectResponse
    {
        $dados = $request->validate(['senha_atual' => $this->currentPasswordRules(), 'senha' => $this->passwordRules()]);

        $responsavel = $request->user();
        $responsavel->forceFill(['password' => $dados['senha'], 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $responsavel->id)->where('id', '!=', $request->session()->getId())->delete();

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['senha' => $this->currentPasswordRules()]);
        $responsavel = $request->user();

        $this->vinculos->exigirPodeApagarResponsavel($responsavel);

        // Sair antes de apagar: o logout regrava o remember_token e recriaria o usuário já excluído.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->vinculos->apagarResponsavel($responsavel);

        return redirect('/');
    }
}
