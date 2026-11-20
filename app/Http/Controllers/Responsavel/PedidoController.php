<?php

namespace App\Http\Controllers\Responsavel;

use App\Concerns\ParentescoValidationRules;
use App\Domain\Responsavel\ConviteIndisponivel;
use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\PedidoDeVinculo;
use App\Enums\TipoConvite;
use App\Http\Controllers\Controller;
use App\Models\ConviteResponsavel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Adicionar aluno": pedido iniciado pelo responsável, mais a aprovação dos pedidos de outros adultos. */
class PedidoController extends Controller
{
    use ParentescoValidationRules;

    public const string STATUS_GENERICO = 'Se existir um aluno com esse e-mail, ele receberá o pedido.';

    public function __construct(private readonly PedidoDeVinculo $pedidos) {}

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'declara_responsavel' => ['accepted'],
            ...$this->parentescoRules(),
        ], ['declara_responsavel.accepted' => 'Para pedir o vínculo, confirme que você é responsável legal pelo aluno.']);

        $this->pedidos->solicitar(
            $request->user(),
            $dados['email'],
            DadosDeParentesco::de($dados['parentesco'], $dados['parentesco_outro'] ?? null),
            $request->ip(),
        );

        return back()->with('status', self::STATUS_GENERICO);
    }

    public function aprovar(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        return $this->resolver($request, $convite, fn () => $this->pedidos->aprovarComoResponsavel($convite, $request->user()));
    }

    public function recusar(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        return $this->resolver($request, $convite, fn () => $this->pedidos->recusar($convite));
    }

    /** O solicitante cancela o próprio pedido. */
    public function destroy(Request $request, ConviteResponsavel $convite): RedirectResponse
    {
        abort_unless($convite->solicitante_id === $request->user()->id && $convite->estaPendente()
            && TipoConvite::from($convite->tipo)->ehPedidoDoResponsavel(), 404);

        $convite->forceFill(['invalidado_em' => now()])->save();

        return back();
    }

    /** Só um responsável ativo do aluno pode agir no pedido (IDOR: o resto é 404). */
    private function resolver(Request $request, ConviteResponsavel $convite, callable $acao): RedirectResponse
    {
        $meusAlunos = $request->user()->alunos()->pluck('users.id')->all();

        abort_unless($convite->tipo === TipoConvite::PedidoVinculo->value && $convite->estaPendente()
            && in_array($convite->aluno_id, $meusAlunos, true), 404);

        try {
            $acao();
        } catch (ConviteIndisponivel) {
            abort(404);
        }

        return back();
    }
}
