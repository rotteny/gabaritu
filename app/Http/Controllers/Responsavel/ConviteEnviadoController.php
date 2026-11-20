<?php

namespace App\Http\Controllers\Responsavel;

use App\Concerns\ParentescoValidationRules;
use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\EmissorDeConvite;
use App\Domain\Responsavel\LimitesDeConvite;
use App\Enums\TipoConvite;
use App\Http\Controllers\Controller;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** O responsável convida um co-responsável ou transfere a responsabilidade. */
class ConviteEnviadoController extends Controller
{
    use ParentescoValidationRules;

    public function __construct(private readonly EmissorDeConvite $emissor, private readonly LimitesDeConvite $limites) {}

    public function store(Request $request, User $aluno): RedirectResponse
    {
        $this->authorize('acompanhar', $aluno);
        $dados = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'tipo' => ['required', Rule::in([TipoConvite::Coresponsavel->value, TipoConvite::Transferencia->value])],
            ...$this->parentescoRules(),
        ]);
        $tipo = TipoConvite::from($dados['tipo']);

        $this->limites->exigirEmailDeNovoResponsavel($aluno, $dados['email']);
        if ($tipo->acrescentaResponsavel()) {
            $this->limites->exigirVagaDeResponsavel($aluno);
        }

        $this->emissor->emitir(
            $aluno,
            $tipo,
            $dados['email'],
            DadosDeParentesco::de($dados['parentesco'], $dados['parentesco_outro'] ?? null),
            solicitante: $request->user(),
            substitui: $tipo === TipoConvite::Transferencia ? $request->user() : null,
        );

        return back()->with('status', 'convite-enviado');
    }

    /** Veto: qualquer responsável ativo cancela o convite que o aluno fez enquanto estiver pendente. */
    public function destroy(User $aluno, ConviteResponsavel $convite): RedirectResponse
    {
        $this->authorize('acompanhar', $aluno);
        abort_unless($convite->aluno_id === $aluno->id && $convite->tipo === TipoConvite::CoresponsavelAluno->value && $convite->estaPendente(), 404);

        $convite->forceFill(['invalidado_em' => now()])->save();

        return back();
    }
}
