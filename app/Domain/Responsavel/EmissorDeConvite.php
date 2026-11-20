<?php

namespace App\Domain\Responsavel;

use App\Enums\TipoConvite;
use App\Mail\ConviteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmissorDeConvite
{
    public const int VALIDADE_EM_DIAS = 7;

    private const int TAMANHO_DO_TOKEN = 48;

    public function emitir(
        User $aluno,
        TipoConvite $tipo,
        string $email,
        DadosDeParentesco $parentesco,
        ?User $solicitante = null,
        ?User $substitui = null,
    ): ConviteResponsavel {
        $token = Str::random(self::TAMANHO_DO_TOKEN);
        $this->substituirPendenteParaOMesmoEmail($aluno, $tipo, $email);

        $convite = new ConviteResponsavel([
            'aluno_id' => $aluno->id,
            'solicitante_id' => $solicitante?->id,
            'substitui_responsavel_id' => $substitui?->id,
            'tipo' => $tipo->value,
            'email' => Str::lower(trim($email)),
            'parentesco' => $parentesco->parentesco->value,
            'parentesco_outro' => $parentesco->outro,
            'token_hash' => ConviteResponsavel::hashDoToken($token),
            'expira_em' => now()->addDays(self::VALIDADE_EM_DIAS),
        ]);
        $convite->save();

        Mail::to($convite->email)->send(new ConviteDeResponsavel($convite, $aluno, $token));

        return $convite;
    }

    /** Reenviar o mesmo convite (mesmo aluno, tipo e e-mail) substitui o anterior: nunca ficam dois pendentes. */
    private function substituirPendenteParaOMesmoEmail(User $aluno, TipoConvite $tipo, string $email): void
    {
        ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)
            ->where('tipo', $tipo->value)->where('email', Str::lower(trim($email)))->update(['invalidado_em' => now()]);
    }

    public function invalidarPendentes(User $aluno, TipoConvite $tipo): void
    {
        ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)
            ->where('tipo', $tipo->value)->update(['invalidado_em' => now()]);
    }
}
