<?php

namespace App\Http\Controllers;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Responsavel\ConviteDoAluno;
use App\Domain\Responsavel\EmailDeResponsavel;
use App\Domain\Responsavel\LimitesDeConvite;
use App\Support\EmailMascarado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AguardandoResponsavelController extends Controller
{
    public function __construct(private readonly ConviteDoAluno $convites, private readonly LimitesDeConvite $limites, private readonly EmailDeResponsavel $emails) {}

    public function show(Request $request, FalaService $falas): Response
    {
        $aluno = $request->user();
        $ultimo = $this->convites->ultimo($aluno);

        return Inertia::render('aluno/AguardandoResponsavel', [
            'email_responsavel_mascarado' => EmailMascarado::de((string) $aluno->email_responsavel),
            'convite_expira_em' => $ultimo?->estaPendente() ? $ultimo->expira_em->toIso8601String() : null,
            'recusado' => $ultimo?->recusado_em !== null,
            'reenvios_restantes' => $this->limites->reenviosRestantes($aluno),
            'fala' => $falas->sortear($aluno, ['aguardando_responsavel'], Fala::HUMOR_NEUTRO),
        ]);
    }

    public function reenviar(Request $request): RedirectResponse
    {
        $this->convites->reenviar($request->user());

        return back()->with('status', 'convite-reenviado');
    }

    public function atualizarEmail(Request $request): RedirectResponse
    {
        $aluno = $request->user();
        $dados = $request->validate(['email_responsavel' => ['required', 'string', 'email', 'max:255']]);
        $this->emails->exigir($dados['email_responsavel'], $aluno->email, 'email_responsavel');

        $this->convites->corrigirEmail($aluno, $dados['email_responsavel']);

        return back()->with('status', 'email-corrigido');
    }
}
