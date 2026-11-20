<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Mail\AvisoRemocaoPorPendencia;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
        $candidatos = $this->removiveis()->pluck('id');

        return $candidatos->filter(fn (int $id) => $this->apagarSeAindaRemovivel($id, fn () => $this->removiveis()))->count();
    }

    private function removerResponsaveisSemAluno(): int
    {
        $candidatos = $this->responsaveisSemAluno()->pluck('id');

        return $candidatos->filter(fn (int $id) => $this->apagarSeAindaRemovivel($id, fn () => $this->responsaveisSemAluno()))->count();
    }

    /** Reconfere o critério com a linha travada, na mesma transação do delete: quem foi autorizado nesse meio tempo fica. */
    private function apagarSeAindaRemovivel(int $id, callable $criterio): bool
    {
        return DB::transaction(function () use ($id, $criterio): bool {
            $usuario = $criterio()->whereKey($id)->lockForUpdate()->first();

            if ($usuario === null) {
                return false;
            }

            $this->vinculos->apagarUsuario($usuario);

            return true;
        });
    }

    /** @return Builder<User> */
    private function removiveis(): Builder
    {
        return $this->pendentes()
            ->where('pendente_desde', '<=', now()->subDays(self::DIAS_PENDENTE))
            ->where('aviso_remocao_em', '<=', now()->subDays(self::DIAS_DE_AVISO));
    }

    /** @return Builder<User> */
    private function responsaveisSemAluno(): Builder
    {
        return User::query()->where('tipo', TipoConta::Responsavel->value)
            ->where('sem_aluno_desde', '<=', now()->subDays(self::DIAS_RESPONSAVEL_SEM_ALUNO))
            ->whereDoesntHave('vinculosAtivosComAlunos');
    }

    /** @return Builder<User> */
    private function pendentes(): Builder
    {
        return User::query()->where('tipo', TipoConta::Aluno->value)->where('status', StatusConta::AguardandoResponsavel->value);
    }
}
