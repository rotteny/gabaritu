<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Mail\AvisoRemocaoPorPendencia;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

/** Retenção (docs/responsaveis.md, regra 12). */
class LimpezaDePendentes
{
    public const int DIAS_PENDENTE = 30;

    public const int DIAS_DE_AVISO = 3;

    public const int DIAS_RESPONSAVEL_SEM_ALUNO = 30;

    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    /** @return array{avisados: int, removidos: int, convites: int, responsaveis: int} */
    public function executar(): array
    {
        return [
            'avisados' => $this->avisarPendentes(),
            'removidos' => $this->removerPendentes(),
            'convites' => ConviteResponsavel::query()->where('expira_em', '<', now())->delete(),
            'responsaveis' => $this->removerResponsaveisSemAluno(),
        ];
    }

    private function avisarPendentes(): int
    {
        $limite = now()->subDays(self::DIAS_PENDENTE - self::DIAS_DE_AVISO);
        $pendentes = $this->pendentes()->whereNull('aviso_remocao_em')->where('pendente_desde', '<=', $limite)->get();

        foreach ($pendentes as $aluno) {
            Mail::to($aluno->email)->send(new AvisoRemocaoPorPendencia($aluno, self::DIAS_DE_AVISO));
            $aluno->forceFill(['aviso_remocao_em' => now()])->save();
        }

        return $pendentes->count();
    }

    private function removerPendentes(): int
    {
        $removiveis = $this->pendentes()
            ->where('pendente_desde', '<=', now()->subDays(self::DIAS_PENDENTE))
            ->where('aviso_remocao_em', '<=', now()->subDays(self::DIAS_DE_AVISO))->get();

        $removiveis->each(fn (User $aluno) => $this->vinculos->apagarUsuario($aluno));

        return $removiveis->count();
    }

    private function removerResponsaveisSemAluno(): int
    {
        $sem = User::query()->where('tipo', TipoConta::Responsavel->value)
            ->where('sem_aluno_desde', '<=', now()->subDays(self::DIAS_RESPONSAVEL_SEM_ALUNO))
            ->whereDoesntHave('vinculosAtivosComAlunos')->get();

        $sem->each(fn (User $responsavel) => $this->vinculos->apagarUsuario($responsavel));

        return $sem->count();
    }

    /** @return Builder<User> */
    private function pendentes(): Builder
    {
        return User::query()->where('tipo', TipoConta::Aluno->value)->where('status', StatusConta::AguardandoResponsavel->value);
    }
}
