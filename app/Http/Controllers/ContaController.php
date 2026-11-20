<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Domain\Responsavel\ApresentadorDeAluno;
use App\Domain\Responsavel\GestorDeVinculos;
use App\Domain\Responsavel\LimitesDeConvite;
use App\Enums\Nivel;
use App\Enums\Serie;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContaController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, ApresentadorDeAluno $apresentador, LimitesDeConvite $limites): Response
    {
        $aluno = $request->user();

        return Inertia::render('Conta', [
            'perfil' => ['apelido' => $aluno->apelido, 'email' => $aluno->email, 'serie' => $aluno->serie, 'avatar' => $aluno->avatar],
            'avatares' => config('gabaritu.avatares'),
            'series' => array_map(fn (Serie $serie) => $serie->value, Serie::cases()),
            'responsaveis' => $apresentador->responsaveisParaAluno($aluno),
            'convites_responsavel' => $apresentador->convitesParaAluno($aluno),
            'pedidos_vinculo' => $apresentador->pedidosParaAluno($aluno),
            'pode_convidar' => ! $aluno->aguardaResponsavel() && $limites->podeConvidar($aluno),
        ]);
    }

    public function preferencias(Request $request): RedirectResponse
    {
        $aluno = $request->user();
        $dados = $request->validate([
            'zoeira' => ['sometimes', Rule::enum(Nivel::class)],
            'som' => ['sometimes', 'boolean'],
            'musica' => ['sometimes', 'boolean'],
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
            'avatar' => ['sometimes', 'required', Rule::in(config('gabaritu.avatares'))],
        ]));

        return back();
    }

    public function senha(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'senha_atual' => $this->currentPasswordRules(),
            'senha' => $this->passwordRules(),
        ]);

        $aluno = $request->user();
        $aluno->forceFill(['password' => $dados['senha'], 'remember_token' => Str::random(60)])->save();
        // Senha nova derruba quem estava logado em outro aparelho (inclusive o "lembrar de mim").
        DB::table('sessions')->where('user_id', $aluno->id)->where('id', '!=', $request->session()->getId())->delete();

        return back();
    }

    public function destroy(Request $request, GestorDeVinculos $vinculos): RedirectResponse
    {
        $request->validate(['senha' => $this->currentPasswordRules()]);
        $aluno = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Apaga sessões, token de senha e o aluno, e marca o responsável que ficou sem aluno (entra na limpeza de 30 dias).
        $vinculos->apagarAluno($aluno);

        return redirect('/');
    }

    private function recusarZoeiraAcimaDoTeto(User $aluno, ?string $zoeira): void
    {
        if ($zoeira !== null && Nivel::from($zoeira)->ordem() > $aluno->zoeiraMaxima()->ordem()) {
            $porIdade = Nivel::from($zoeira)->ordem() > $aluno->zoeiraMaximaPorIdade()->ordem();

            throw ValidationException::withMessages(['zoeira' => $porIdade
                ? 'Esse nível de zoeira só é liberado a partir dos 15 anos.'
                : 'O seu responsável limitou o nível de zoeira. Esse nível não está liberado para você.']);
        }
    }
}
