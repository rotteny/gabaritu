<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Enums\TipoConvite;
use App\Mail\AlunoAutorizado;
use App\Mail\AutorizacaoConfirmada;
use App\Mail\AutorizacaoRecusada;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AceitadorDeConvite
{
    public function __construct(private readonly GestorDeVinculos $vinculos, private readonly RegistradorDeVinculo $registrador) {}

    /**
     * @param  array{parentesco: string, parentesco_outro?: string|null, nome?: string, senha?: string, ano_nascimento?: int|string}  $dados
     */
    public function aceitar(ConviteResponsavel $convite, ?User $responsavel, array $dados, ?string $ip): User
    {
        $resultado = DB::transaction(function () use ($convite, $responsavel, $dados, $ip): array {
            $atual = $this->travar($convite);
            $aluno = User::query()->lockForUpdate()->findOrFail($atual->aluno_id);
            $responsavel ??= $this->criarResponsavel($atual, $dados);

            $vinculo = $this->gravarVinculo($aluno, $responsavel, $dados, $ip);
            $this->encerrarVinculoSubstituido($atual, $responsavel);
            $atual->forceFill(['usado_em' => now()])->save();

            return [$responsavel, $aluno, $this->registrador->liberarAluno($aluno), $vinculo];
        });

        [$responsavel, $aluno, $foiAtivado, $vinculo] = $resultado;
        Mail::to($responsavel->email)->send(new AutorizacaoConfirmada($aluno, $responsavel, $vinculo));
        if ($foiAtivado) {
            Mail::to($aluno->email)->send(new AlunoAutorizado($aluno, $responsavel));
        }

        return $responsavel;
    }

    public function recusar(ConviteResponsavel $convite): void
    {
        DB::transaction(function () use ($convite): void {
            $this->travar($convite)->forceFill(['recusado_em' => now()])->save();
        });

        if ($convite->tipo === TipoConvite::Autorizacao->value) {
            $aluno = User::query()->findOrFail($convite->aluno_id);
            Mail::to($aluno->email)->send(new AutorizacaoRecusada($aluno));
        }
    }

    private function travar(ConviteResponsavel $convite): ConviteResponsavel
    {
        $atual = ConviteResponsavel::query()->lockForUpdate()->find($convite->id);

        if ($atual === null || ! $atual->estaPendente()) {
            throw new ConviteIndisponivel;
        }

        return $atual;
    }

    /** @param  array<string, mixed>  $dados */
    private function criarResponsavel(ConviteResponsavel $convite, array $dados): User
    {
        if (User::query()->whereRaw('lower(email) = ?', [$convite->email])->exists()) {
            throw ValidationException::withMessages(['email' => EmailDeResponsavel::MENSAGEM]);
        }

        $responsavel = new User(['apelido' => $dados['nome'], 'email' => $convite->email, 'password' => $dados['senha'], 'ano_nascimento' => (int) $dados['ano_nascimento']]);
        $responsavel->forceFill([
            'tipo' => TipoConta::Responsavel->value,
            'status' => StatusConta::Ativo->value,
            'zoeira' => 'fofo',
            'privacidade_aceita_em' => now(),
            'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
        ])->save();

        return $responsavel;
    }

    /** @param  array<string, mixed>  $dados */
    private function gravarVinculo(User $aluno, User $responsavel, array $dados, ?string $ip): VinculoResponsavel
    {
        $parentesco = DadosDeParentesco::de((string) $dados['parentesco'], $dados['parentesco_outro'] ?? null);

        return $this->registrador->registrar($aluno, $responsavel, $parentesco, now(), $ip);
    }

    private function encerrarVinculoSubstituido(ConviteResponsavel $convite, User $novo): void
    {
        $antigoId = $convite->substitui_responsavel_id;

        if ($convite->tipo !== TipoConvite::Transferencia->value || $antigoId === null || $antigoId === $novo->id) {
            return;
        }

        VinculoResponsavel::query()->where('aluno_id', $convite->aluno_id)->where('responsavel_id', $antigoId)->delete();
        $antigo = User::query()->find($antigoId);
        if ($antigo !== null) {
            $this->vinculos->marcarSemAluno($antigo);
        }
    }
}
