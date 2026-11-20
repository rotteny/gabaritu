<?php

namespace App\Domain\Responsavel;

use App\Mail\ConviteDoAlunoAosResponsaveis;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** Avisos aos responsáveis atuais sobre o que o aluno faz (sem pedir aprovação). */
class AvisoAosResponsaveis
{
    /** O aluno convidou um co-responsável: todos os atuais sabem quem é e podem vetar pelo painel enquanto estiver pendente. */
    public function conviteDoAluno(ConviteResponsavel $convite, User $aluno): void
    {
        $aluno->responsaveis()->get()->each(
            fn (User $atual) => Mail::to($atual->email)->send(new ConviteDoAlunoAosResponsaveis($convite, $aluno)),
        );
    }
}
