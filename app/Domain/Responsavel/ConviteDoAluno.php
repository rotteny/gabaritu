<?php

namespace App\Domain\Responsavel;

use App\Enums\Parentesco;
use App\Enums\StatusConta;
use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Convite de autorização do aluno menor: cadastro, reenvio e correção do e-mail. */
class ConviteDoAluno
{
    public function __construct(private readonly EmissorDeConvite $emissor, private readonly LimitesDeConvite $limites) {}

    public function enviarPrimeiro(User $aluno, string $email, DadosDeParentesco $parentesco): ConviteResponsavel
    {
        $aluno->forceFill([
            'email_responsavel' => Str::lower(trim($email)),
            'parentesco_responsavel' => $parentesco->parentesco->value,
            'parentesco_responsavel_outro' => $parentesco->outro,
            'status' => StatusConta::AguardandoResponsavel->value,
            'pendente_desde' => $aluno->pendente_desde ?? now(),
            'aviso_remocao_em' => null,
        ])->save();

        return $this->emitir($aluno);
    }

    public function reenviar(User $aluno): void
    {
        if ($aluno->email_responsavel === null || $aluno->parentesco_responsavel === null) {
            throw ValidationException::withMessages(['reenvio' => 'Informe o e-mail e o parentesco do responsável para enviar o convite.']);
        }

        $this->limites->exigirReenvio($aluno, 'reenvio');
        $this->emitir($aluno);
    }

    public function corrigirEmail(User $aluno, string $email, ?DadosDeParentesco $parentesco = null): void
    {
        $this->limites->exigirReenvio($aluno, 'email_responsavel');
        $aluno->forceFill([
            'email_responsavel' => Str::lower(trim($email)),
            ...($parentesco === null ? [] : ['parentesco_responsavel' => $parentesco->parentesco->value, 'parentesco_responsavel_outro' => $parentesco->outro]),
        ])->save();
        $this->emitir($aluno);
    }

    public function ultimo(User $aluno): ?ConviteResponsavel
    {
        return ConviteResponsavel::query()->where('aluno_id', $aluno->id)
            ->where('tipo', TipoConvite::Autorizacao->value)->latest('id')->first();
    }

    private function emitir(User $aluno): ConviteResponsavel
    {
        $this->emissor->invalidarPendentes($aluno, TipoConvite::Autorizacao);

        return $this->emissor->emitir(
            $aluno,
            TipoConvite::Autorizacao,
            (string) $aluno->email_responsavel,
            new DadosDeParentesco(Parentesco::from((string) $aluno->parentesco_responsavel), $aluno->parentesco_responsavel_outro),
        );
    }
}
