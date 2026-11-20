<?php

namespace App\Domain\Responsavel;

use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LimitesDeConvite
{
    public function __construct(private readonly EmailDeResponsavel $emails) {}

    public const int REENVIOS_POR_DIA = 3;

    public const int CONVITES_DO_ALUNO_POR_DIA = 3;

    public const int MAX_RESPONSAVEIS = 5;

    /** O primeiro envio (cadastro) não conta como reenvio; correção de e-mail conta. */
    public function reenviosRestantes(User $aluno): int
    {
        $enviados = $this->criadosNoDia($aluno, TipoConvite::Autorizacao);

        return max(0, self::REENVIOS_POR_DIA - max(0, $enviados - 1));
    }

    public function exigirReenvio(User $aluno, string $campo): void
    {
        if ($this->reenviosRestantes($aluno) === 0) {
            throw ValidationException::withMessages([$campo => 'Você já usou os 3 reenvios de hoje. Tente de novo amanhã.']);
        }
    }

    public function exigirPedidoDoAluno(User $aluno): void
    {
        if ($this->criadosNoDia($aluno, TipoConvite::CoresponsavelAluno) >= self::CONVITES_DO_ALUNO_POR_DIA) {
            throw ValidationException::withMessages(['email' => 'Você já fez 3 convites hoje. Tente de novo amanhã.']);
        }
    }

    public function exigirVagaDeResponsavel(User $aluno): void
    {
        if ($this->vagasUsadas($aluno) >= self::MAX_RESPONSAVEIS) {
            throw ValidationException::withMessages(['email' => 'Cada aluno pode ter no máximo 5 responsáveis.']);
        }
    }

    public function exigirEmailDeNovoResponsavel(User $aluno, string $email): void
    {
        $this->emails->exigir($email, $aluno->email, 'email');

        $normalizado = EmailDeResponsavel::normalizar($email);
        $jaVinculado = $aluno->responsaveis()->get()->contains(fn (User $r) => EmailDeResponsavel::normalizar($r->email) === $normalizado);

        if ($jaVinculado) {
            throw ValidationException::withMessages(['email' => 'Este e-mail já é de um responsável deste aluno.']);
        }
    }

    public function podeConvidar(User $aluno): bool
    {
        return $this->vagasUsadas($aluno) < self::MAX_RESPONSAVEIS
            && $this->criadosNoDia($aluno, TipoConvite::CoresponsavelAluno) < self::CONVITES_DO_ALUNO_POR_DIA;
    }

    private function vagasUsadas(User $aluno): int
    {
        $pendentes = ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)
            ->whereIn('tipo', [TipoConvite::Coresponsavel->value, TipoConvite::CoresponsavelAluno->value])->count();

        return $aluno->vinculosComResponsaveis()->count() + $pendentes;
    }

    private function criadosNoDia(User $aluno, TipoConvite $tipo): int
    {
        return ConviteResponsavel::query()->where('aluno_id', $aluno->id)
            ->where('tipo', $tipo->value)->where('created_at', '>=', now()->subDay())->count();
    }
}
