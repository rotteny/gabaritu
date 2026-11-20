<?php

namespace App\Http\Controllers\Responsavel;

use App\Domain\Responsavel\AceitadorDeConvite;
use App\Domain\Responsavel\ConviteIndisponivel;
use App\Domain\Responsavel\EmailDeResponsavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegrasDeAceite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConviteRecebidoController extends Controller
{
    public function __construct(private readonly AceitadorDeConvite $aceitador) {}

    public function show(Request $request, string $token): Response
    {
        $convite = $this->localizar($token);

        if ($convite === null) {
            return $this->paginaIndisponivel($token);
        }

        $usuario = $request->user();

        return Inertia::render('responsavel/Convite', [
            'token' => $token,
            'vencido' => false,
            'convite' => [
                'aluno' => ['apelido' => $convite->aluno->apelido, 'serie' => $convite->aluno->serie],
                'email' => $convite->email,
                'tipo' => $convite->tipo,
                'parentesco' => $convite->parentesco,
                'parentesco_outro' => $convite->parentesco_outro,
                'expira_em' => $convite->expira_em->toIso8601String(),
            ],
            'tem_conta' => $this->contaDoDestinatario($convite) !== null,
            'logado_como_destinatario' => $usuario !== null && $this->ehDestinatario($usuario, $convite),
        ]);
    }

    public function aceitar(Request $request, string $token): RedirectResponse|Response
    {
        $convite = $this->localizar($token);

        if ($convite === null) {
            return $this->paginaIndisponivel($token);
        }

        $responsavel = $this->responsavelDaRequisicao($request, $convite);
        if ($responsavel instanceof RedirectResponse) {
            return $responsavel;
        }

        $regras = new RegrasDeAceite;
        $dados = $request->validate($regras->regras($responsavel === null), $regras->mensagens());

        try {
            $novo = $this->aceitador->aceitar($convite, $responsavel, $dados, $request->ip());
        } catch (ConviteIndisponivel) {
            return $this->paginaIndisponivel($token);
        }

        Auth::login($novo);
        $request->session()->regenerate();

        return redirect('/responsavel');
    }

    public function recusar(string $token): RedirectResponse|Response
    {
        $convite = $this->localizar($token);

        if ($convite === null) {
            return $this->paginaIndisponivel($token);
        }

        try {
            $this->aceitador->recusar($convite);
        } catch (ConviteIndisponivel) {
            return $this->paginaIndisponivel($token);
        }

        return redirect('/');
    }

    private function localizar(string $token): ?ConviteResponsavel
    {
        $convite = ConviteResponsavel::query()->with('aluno')->where('token_hash', ConviteResponsavel::hashDoToken($token))->first();

        return $convite?->estaPendente() ? $convite : null;
    }

    /** Token inexistente, usado, recusado ou vencido: a mesma página, sem convite (`vencido` só quando o prazo passou). */
    private function paginaIndisponivel(string $token): Response
    {
        $existente = ConviteResponsavel::query()->where('token_hash', ConviteResponsavel::hashDoToken($token))->first();

        return Inertia::render('responsavel/Convite', [
            'token' => $token,
            'convite' => null,
            'vencido' => $existente !== null && $existente->usado_em === null && $existente->recusado_em === null
                && $existente->invalidado_em === null && $existente->expira_em->isPast(),
            'tem_conta' => false,
            'logado_como_destinatario' => false,
        ]);
    }

    /** Sem sessão: cria a conta (se o e-mail estiver livre) ou pede login. Com sessão: só o destinatário. */
    private function responsavelDaRequisicao(Request $request, ConviteResponsavel $convite): User|RedirectResponse|null
    {
        $usuario = $request->user();

        if ($usuario !== null) {
            if (! $usuario->ehResponsavel()) {
                throw ValidationException::withMessages(['email' => EmailDeResponsavel::MENSAGEM]);
            }

            abort_unless($this->ehDestinatario($usuario, $convite), HttpResponse::HTTP_FORBIDDEN);

            return $usuario;
        }

        $existente = $this->contaDoDestinatario($convite);

        if ($existente === null) {
            return null;
        }
        $token = $request->route('token');

        if (! $existente->ehResponsavel()) {
            throw ValidationException::withMessages(['email' => EmailDeResponsavel::MENSAGEM]);
        }

        return redirect('/responsavel/entrar?redirect='.urlencode("/responsavel/convite/{$token}"));
    }

    private function contaDoDestinatario(ConviteResponsavel $convite): ?User
    {
        return User::query()->whereRaw('lower(email) = ?', [Str::lower($convite->email)])->first();
    }

    private function ehDestinatario(User $usuario, ConviteResponsavel $convite): bool
    {
        return $usuario->ehResponsavel() && Str::lower($usuario->email) === Str::lower($convite->email);
    }
}
