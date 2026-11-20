<?php

namespace App\Http\Controllers;

use App\Concerns\ParentescoValidationRules;
use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\EmissorDeConvite;
use App\Domain\Responsavel\GestorDeVinculos;
use App\Domain\Responsavel\LimitesDeConvite;
use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** O aluno pede a inclusão de outro responsável e cancela convites pendentes. Nunca remove um responsável ativo. */
class ContaResponsaveisController extends Controller
{
    use ParentescoValidationRules;

    public function __construct(private readonly EmissorDeConvite $emissor, private readonly LimitesDeConvite $limites) {}

    public function store(Request $request): RedirectResponse
    {
        $aluno = $request->user();
        $dados = $request->validate(['email' => ['required', 'string', 'email', 'max:255'], ...$this->parentescoRules()]);

        $this->limites->exigirPedidoDoAluno($aluno);
        $this->limites->exigirVagaDeResponsavel($aluno);
        $this->limites->exigirEmailDeNovoResponsavel($aluno, $dados['email']);

        $this->emissor->emitir(
            $aluno,
            TipoConvite::CoresponsavelAluno,
            $dados['email'],
            DadosDeParentesco::de($dados['parentesco'], $dados['parentesco_outro'] ?? null),
            solicitante: $aluno,
        );

        return back()->with('status', 'convite-enviado');
    }

    /** "Essa pessoa não é meu responsável": suspende o acesso dele na hora. */
    public function contestar(Request $request, User $responsavel, GestorDeVinculos $vinculos): RedirectResponse
    {
        $vinculos->contestar($request->user(), $responsavel);

        return back();
    }

    public function destroy(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        abort_unless($convite->aluno_id === $request->user()->id && $convite->estaPendente(), 404);

        $convite->forceFill(['invalidado_em' => now()])->save();

        return back();
    }
}
