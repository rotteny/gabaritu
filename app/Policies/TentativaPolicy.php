<?php

namespace App\Policies;

use App\Models\Tentativa;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TentativaPolicy
{
    public function ver(User $aluno, Tentativa $tentativa): Response
    {
        return $aluno->id === $tentativa->user_id ? Response::allow() : Response::denyAsNotFound();
    }
}
