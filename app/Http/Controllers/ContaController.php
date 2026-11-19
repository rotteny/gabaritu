<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Enums\Nivel;
use App\Enums\Serie;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContaController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request): Response
    {
        $aluno = $request->user();

        return Inertia::render('Conta', [
            'perfil' => ['apelido' => $aluno->apelido, 'email' => $aluno->email, 'serie' => $aluno->serie],
            'series' => array_map(fn (Serie $serie) => $serie->value, Serie::cases()),
        ]);
    }

    public function preferencias(Request $request): RedirectResponse
    {
        $aluno = $request->user();
        $dados = $request->validate([
            'zoeira' => ['sometimes', Rule::enum(Nivel::class)],
            'som' => ['sometimes', 'boolean'],
            'reduzir_movimento' => ['sometimes', 'boolean'],
        ]);

        $this->recusarZoeiraAcimaDoTeto($aluno, $dados['zoeira'] ?? null);
        $aluno->update($dados);

        return back();
    }

    public function perfil(Request $request): RedirectResponse
    {
        $request->user()->update($request->validate([
            'apelido' => ['required', 'string', 'min:2', 'max:30'],
            'serie' => ['required', Rule::enum(Serie::class)],
        ]));

        return back();
    }

    public function senha(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'senha_atual' => $this->currentPasswordRules(),
            'senha' => $this->passwordRules(),
        ]);

        $request->user()->update(['password' => $dados['senha']]);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['senha' => $this->currentPasswordRules()]);
        $aluno = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($aluno): void {
            DB::table('sessions')->where('user_id', $aluno->id)->delete();
            DB::table('password_reset_tokens')->where('email', $aluno->email)->delete();
            $aluno->delete();
        });

        return redirect('/');
    }

    private function recusarZoeiraAcimaDoTeto(User $aluno, ?string $zoeira): void
    {
        if ($zoeira !== null && Nivel::from($zoeira)->ordem() > $aluno->zoeiraMaxima()->ordem()) {
            throw ValidationException::withMessages(['zoeira' => 'Esse nível de zoeira só é liberado a partir dos 15 anos.']);
        }
    }
}
