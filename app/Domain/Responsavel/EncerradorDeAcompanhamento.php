<?php

namespace App\Domain\Responsavel;

use App\Mail\AcompanhamentoEncerrado;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** Aluno com 18 anos ou mais encerra todo o acompanhamento: sem volta (os vínculos somem e não há o que restaurar). */
class EncerradorDeAcompanhamento
{
    public function __construct(private readonly GestorDeVinculos $vinculos) {}

    public function encerrar(User $aluno): void
    {
        $responsaveis = DB::transaction(function () use ($aluno): Collection {
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            $this->exigirMaiorDeIdade($aluno);

            $responsaveis = User::query()->whereIn('id', $aluno->vinculosComResponsaveis()->select('responsavel_id'))->get();
            $aluno->vinculosComResponsaveis()->delete();
            ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)->update(['invalidado_em' => now()]);
            $responsaveis->each(fn (User $responsavel) => $this->vinculos->marcarSemAluno($responsavel));

            return $responsaveis;
        });

        $responsaveis->each(fn (User $responsavel) => Mail::to($responsavel->email)->send(new AcompanhamentoEncerrado($aluno, $responsavel)));
    }

    private function exigirMaiorDeIdade(User $aluno): void
    {
        if ($aluno->precisaDeResponsavel()) {
            throw ValidationException::withMessages(['acompanhamento' => 'Só quem tem 18 anos ou mais pode encerrar o acompanhamento.']);
        }
    }
}
