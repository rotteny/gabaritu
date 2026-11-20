<?php

namespace App\Domain\Responsavel;

use App\Enums\TipoConta;
use App\Enums\TipoConvite;
use App\Mail\AutorizacaoConfirmada;
use App\Mail\PedidoDeResponsavelAoAluno;
use App\Mail\PedidoDeVinculoAosResponsaveis;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pedido iniciado pelo responsável ("Adicionar aluno"). O aluno sempre precisa aceitar e, se já tiver
 * responsável ativo, um deles também precisa aprovar. O resultado do pedido nunca revela se o e-mail tem conta.
 */
class PedidoDeVinculo
{
    public const int PEDIDOS_POR_DIA = 5;

    private const int SEGUNDOS_NO_DIA = 86400;

    private const int TAMANHO_DO_TOKEN = 48;

    public function __construct(private readonly RegistradorDeVinculo $registrador) {}

    public function solicitar(User $solicitante, string $email, DadosDeParentesco $parentesco, ?string $ip): void
    {
        $this->limitar($solicitante);

        $email = Str::lower(trim($email));
        $destino = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($this->jaHaPedidoPendente($solicitante, $email)) {
            return;
        }

        if ($destino === null) {
            $this->pedirParaNovoAluno($solicitante, $email, $parentesco, $ip);
        } elseif ($this->podeReceberPedido($destino, $solicitante)) {
            $this->pedirParaAlunoExistente($solicitante, $destino, $parentesco, $ip);
        }
    }

    /** Pedido sem conta (link de cadastro) ainda válido e cujo responsável continua existindo. */
    public function localizarParaCadastro(?string $token): ?ConviteResponsavel
    {
        if ($token === null || $token === '') {
            return null;
        }

        $convite = ConviteResponsavel::query()->pendentes()->with('solicitante')
            ->where('tipo', TipoConvite::PedidoResponsavel->value)->where('token_hash', ConviteResponsavel::hashDoToken($token))->first();

        return $convite?->solicitante?->ehResponsavel() ? $convite : null;
    }

    public function aceitarComoAluno(ConviteResponsavel $convite, User $aluno): void
    {
        $this->resolver($convite, function (ConviteResponsavel $atual) use ($aluno): void {
            $atual->forceFill(['aluno_aceitou_em' => now()])->save();
            $this->concluirSePronto($atual, $aluno);
        });
    }

    public function aprovarComoResponsavel(ConviteResponsavel $convite, User $responsavel): void
    {
        $this->resolver($convite, function (ConviteResponsavel $atual) use ($responsavel): void {
            $atual->forceFill(['aprovado_em' => now(), 'aprovado_por_id' => $responsavel->id])->save();
            $this->concluirSePronto($atual, User::query()->findOrFail($atual->aluno_id));
        });
    }

    public function recusar(ConviteResponsavel $convite): void
    {
        $this->resolver($convite, fn (ConviteResponsavel $atual) => $atual->forceFill(['recusado_em' => now()])->save());
    }

    /** Aluno que se cadastrou pelo link do pedido: já nasce vinculado, sem passar pela espera. */
    public function aceitarNoCadastro(ConviteResponsavel $convite, User $aluno): void
    {
        $convite->forceFill(['aluno_id' => $aluno->id])->save();
        $this->aceitarComoAluno($convite, $aluno);
    }

    private function limitar(User $solicitante): void
    {
        $chave = "pedido-vinculo:{$solicitante->id}";

        if (RateLimiter::tooManyAttempts($chave, self::PEDIDOS_POR_DIA)) {
            throw ValidationException::withMessages(['email' => 'Você já fez 5 pedidos hoje. Tente de novo amanhã.']);
        }

        RateLimiter::hit($chave, self::SEGUNDOS_NO_DIA);
    }

    private function jaHaPedidoPendente(User $solicitante, string $email): bool
    {
        return ConviteResponsavel::query()->pendentes()->pedidos()
            ->where('solicitante_id', $solicitante->id)->where('email', $email)->exists();
    }

    private function podeReceberPedido(User $destino, User $solicitante): bool
    {
        return $destino->tipo === TipoConta::Aluno->value
            && $destino->precisaDeResponsavel()
            && ! $destino->responsaveis()->where('users.id', $solicitante->id)->exists();
    }

    private function pedirParaNovoAluno(User $solicitante, string $email, DadosDeParentesco $parentesco, ?string $ip): void
    {
        [$convite, $token] = $this->criar($solicitante, null, $email, TipoConvite::PedidoResponsavel, $parentesco, $ip, false);

        Mail::to($email)->send(new PedidoDeResponsavelAoAluno($convite, $solicitante, $token));
    }

    private function pedirParaAlunoExistente(User $solicitante, User $aluno, DadosDeParentesco $parentesco, ?string $ip): void
    {
        $atuais = $aluno->responsaveis()->get();
        [$convite] = $this->criar($solicitante, $aluno, $aluno->email, TipoConvite::PedidoVinculo, $parentesco, $ip, $atuais->isNotEmpty());

        Mail::to($aluno->email)->send(new PedidoDeResponsavelAoAluno($convite, $solicitante, null));
        $atuais->each(fn (User $atual) => Mail::to($atual->email)->send(new PedidoDeVinculoAosResponsaveis($convite, $aluno, $solicitante)));
    }

    /** @return array{0: ConviteResponsavel, 1: string} */
    private function criar(User $solicitante, ?User $aluno, string $email, TipoConvite $tipo, DadosDeParentesco $parentesco, ?string $ip, bool $exigeAprovacao): array
    {
        $token = Str::random(self::TAMANHO_DO_TOKEN);
        $convite = new ConviteResponsavel([
            'aluno_id' => $aluno?->id,
            'solicitante_id' => $solicitante->id,
            'tipo' => $tipo->value,
            'email' => Str::lower(trim($email)),
            'parentesco' => $parentesco->parentesco->value,
            'parentesco_outro' => $parentesco->outro,
            'token_hash' => ConviteResponsavel::hashDoToken($token),
            'expira_em' => now()->addDays(EmissorDeConvite::VALIDADE_EM_DIAS),
            'aceite_ip' => $ip,
            'aceite_em' => now(),
            'termos_versao' => (string) config('gabaritu.termos_versao'),
            'exige_aprovacao' => $exigeAprovacao,
        ]);
        $convite->save();

        return [$convite, $token];
    }

    private function concluirSePronto(ConviteResponsavel $convite, User $aluno): void
    {
        if ($convite->aluno_aceitou_em === null || ($convite->exige_aprovacao && $convite->aprovado_em === null)) {
            return;
        }

        $solicitante = User::query()->findOrFail($convite->solicitante_id);
        $vinculo = $this->registrador->registrar(
            $aluno,
            $solicitante,
            DadosDeParentesco::de($convite->parentesco, $convite->parentesco_outro),
            $convite->aceite_em ?? now(),
            $convite->aceite_ip,
            $convite->termos_versao,
        );
        $this->registrador->liberarAluno($aluno);
        $convite->forceFill(['usado_em' => now()])->save();

        DB::afterCommit(fn () => Mail::to($solicitante->email)->send(new AutorizacaoConfirmada($aluno, $solicitante, $vinculo)));
    }

    private function resolver(ConviteResponsavel $convite, callable $acao): void
    {
        DB::transaction(function () use ($convite, $acao): void {
            $atual = ConviteResponsavel::query()->lockForUpdate()->find($convite->id);

            if ($atual === null || ! $atual->estaPendente()) {
                throw new ConviteIndisponivel;
            }

            $acao($atual);
        });
    }
}
