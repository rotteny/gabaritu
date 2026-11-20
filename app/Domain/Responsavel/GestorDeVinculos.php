<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestorDeVinculos
{
    /** Invariante: aluno menor ativo sempre mantém ao menos 1 vínculo ativo. */
    public function revogar(User $aluno, User $responsavel): void
    {
        if ($aluno->precisaDeResponsavel() && $aluno->vinculosComResponsaveis()->count() <= 1) {
            throw ValidationException::withMessages([
                'vinculo' => 'Você é o único responsável por este aluno. Transfira a responsabilidade para outra pessoa ou apague a conta do aluno.',
            ]);
        }

        DB::transaction(function () use ($aluno, $responsavel): void {
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
        $this->exigirPodeApagarResponsavel($responsavel);
        $this->apagarUsuario($responsavel);
    }

    public function exigirPodeApagarResponsavel(User $responsavel): void
    {
        $unicoDeAlgum = $responsavel->alunos()->get()->contains(
            fn (User $aluno) => $aluno->precisaDeResponsavel() && $aluno->vinculosComResponsaveis()->count() <= 1,
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
        if ($responsavel->vinculosComAlunos()->doesntExist()) {
            $responsavel->forceFill(['sem_aluno_desde' => now()])->save();
        }
    }

    private function voltarParaPendenteSeFicouSemResponsavel(User $aluno): void
    {
        if ($aluno->precisaDeResponsavel() && $aluno->vinculosComResponsaveis()->doesntExist()) {
            $aluno->forceFill(['status' => StatusConta::AguardandoResponsavel->value, 'pendente_desde' => now(), 'aviso_remocao_em' => null])->save();
        }
    }
}
