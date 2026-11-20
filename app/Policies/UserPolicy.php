<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /** O responsável só age sobre aluno com vínculo ativo com ele; senão a resposta é 404 (sem revelar que o aluno existe). */
    public function acompanhar(User $responsavel, User $aluno): Response
    {
        $vinculado = $responsavel->ehResponsavel()
            && $aluno->vinculosAtivosComResponsaveis()->where('responsavel_id', $responsavel->id)->exists();

        return $vinculado ? Response::allow() : Response::denyAsNotFound();
    }
}
