<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Mail\VinculoContestado;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class GestorDeVinculos
{
    /** Invariante: aluno menor ativo sempre mantém ao menos 1 vínculo ativo. */
    public function revogar(User $aluno, User $responsavel): void
    {
        DB::transaction(function () use ($aluno, $responsavel): void {
            // A trava no aluno serializa revogações, contestações e exclusões simultâneas: a contagem abaixo já é a final.
            $aluno = $this->travarAluno($aluno);

            if ($aluno->precisaDeResponsavel() && $aluno->vinculosAtivosComResponsaveis()->count() <= 1) {
                throw ValidationException::withMessages([
                    'vinculo' => 'Você é o único responsável por este aluno. Transfira a responsabilidade para outra pessoa ou apague a conta do aluno.',
                ]);
            }

            $aluno->vinculosComResponsaveis()->where('responsavel_id', $responsavel->id)->delete();
            $this->marcarSemAluno($responsavel);
            $this->voltarParaPendenteSeFicouSemResponsavel($aluno);
        });
    }

    public function apagarAluno(User $aluno): void
    {
        $responsaveis = $aluno->responsaveis()->get();

        DB::transaction(function () use ($aluno, $responsaveis): void {
            $this->apagarUsuario($aluno);
            $responsaveis->each(fn (User $responsavel) => $this->marcarSemAluno($responsavel));
        });
    }

    public function apagarResponsavel(User $responsavel): void
    {
        DB::transaction(function () use ($responsavel): void {
            $this->travarAlunosDe($responsavel);
            $this->exigirPodeApagarResponsavel($responsavel);
            $this->apagarUsuario($responsavel);
        });
    }

    public function exigirPodeApagarResponsavel(User $responsavel): void
    {
        $unicoDeAlgum = $responsavel->alunos()->get()->contains(
            fn (User $aluno) => $aluno->precisaDeResponsavel() && $aluno->vinculosAtivosComResponsaveis()->count() <= 1,
        );

        if ($unicoDeAlgum) {
            throw ValidationException::withMessages(['conta' => 'Você é o único responsável por um aluno. Transfira a responsabilidade ou apague a conta do aluno antes.']);
        }
    }

    public function apagarUsuario(User $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            DB::table('sessions')->where('user_id', $usuario->id)->delete();
            DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
            $usuario->delete();
        });
    }

    public function marcarSemAluno(User $responsavel): void
    {
        if ($responsavel->vinculosAtivosComAlunos()->doesntExist()) {
            $responsavel->forceFill(['sem_aluno_desde' => now()])->save();
        }
    }

    private function voltarParaPendenteSeFicouSemResponsavel(User $aluno): void
    {
        if ($aluno->precisaDeResponsavel() && $aluno->vinculosAtivosComResponsaveis()->doesntExist()) {
            $aluno->forceFill(['status' => StatusConta::AguardandoResponsavel->value, 'pendente_desde' => now(), 'aviso_remocao_em' => null])->save();
        }
    }

    /** O aluno diz que aquela pessoa não é seu responsável: o acesso é suspenso na hora. */
    public function contestar(User $aluno, User $contestado): void
    {
        $vinculo = DB::transaction(function () use ($aluno, $contestado): VinculoResponsavel {
            $aluno = $this->travarAluno($aluno);
            $vinculo = $aluno->vinculosAtivosComResponsaveis()->where('responsavel_id', $contestado->id)->firstOrFail();
            $vinculo->forceFill(['status' => VinculoResponsavel::SUSPENSO, 'suspenso_em' => now()])->save();
            $this->marcarSemAluno($contestado);
            $this->voltarParaPendenteSeFicouSemResponsavel($aluno);

            return $vinculo;
        });

        $destinos = $aluno->responsaveis()->pluck('email')->push((string) config('gabaritu.email_contato'))->all();
        foreach ($destinos as $destino) {
            Mail::to($destino)->send(new VinculoContestado($aluno, $contestado, $vinculo));
        }
    }

    public function confirmarRemocao(User $aluno, User $alvo): void
    {
        DB::transaction(function () use ($aluno, $alvo): void {
            $this->travarAluno($aluno)->vinculosComResponsaveis()->where('responsavel_id', $alvo->id)
                ->where('status', VinculoResponsavel::SUSPENSO)->firstOrFail()->delete();
            $this->marcarSemAluno($alvo);
        });
    }

    public function restaurar(User $aluno, User $alvo): void
    {
        DB::transaction(function () use ($aluno, $alvo): void {
            $aluno = $this->travarAluno($aluno);
            // Aluno de 18 anos ou mais que contestou decidiu: o vínculo não volta, só pode ser removido.
            if (! $aluno->precisaDeResponsavel()) {
                throw ValidationException::withMessages(['vinculo' => 'Este aluno tem 18 anos ou mais e contestou o vínculo. Ele não pode ser restaurado.']);
            }

            $vinculo = $aluno->vinculosComResponsaveis()->where('responsavel_id', $alvo->id)
                ->where('status', VinculoResponsavel::SUSPENSO)->firstOrFail();

            $vinculo->forceFill(['status' => VinculoResponsavel::ATIVO, 'suspenso_em' => null])->save();
            $alvo->forceFill(['sem_aluno_desde' => null])->save();
        });
    }

    /** Relê o aluno com trava de linha (SELECT ... FOR UPDATE); só tem efeito dentro de uma transação. */
    private function travarAluno(User $aluno): User
    {
        return User::query()->lockForUpdate()->findOrFail($aluno->id);
    }

    /** Trava, em ordem de id (evita deadlock), todos os alunos ligados ao responsável. */
    private function travarAlunosDe(User $responsavel): void
    {
        $ids = VinculoResponsavel::query()->where('responsavel_id', $responsavel->id)->pluck('aluno_id');

        User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }
}
