<?php

namespace App\Domain\Responsavel;

use App\Enums\TipoConta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Quem pode ser indicado como responsável: nunca o próprio aluno nem o e-mail de uma conta de aluno. */
class EmailDeResponsavel
{
    public const string MENSAGEM = 'Use o e-mail de um adulto responsável por você. Esse e-mail não pode ser usado como responsável.';

    private const array DOMINIOS_COM_TAG = ['gmail.com', 'googlemail.com'];

    /** trim, minúsculas e, em Gmail, sem a `+tag` simples. */
    public static function normalizar(string $email): string
    {
        $email = mb_strtolower(trim($email));
        [$usuario, $dominio] = array_pad(explode('@', $email, 2), 2, '');

        if (in_array($dominio, self::DOMINIOS_COM_TAG, true)) {
            $usuario = explode('+', $usuario, 2)[0];
        }

        return $usuario.'@'.$dominio;
    }

    public function usavel(string $email, ?string $emailDoAluno = null): bool
    {
        $normalizado = self::normalizar($email);

        if ($emailDoAluno !== null && self::normalizar($emailDoAluno) === $normalizado) {
            return false;
        }

        return ! $this->pertenceAContaDeAluno($email);
    }

    public function exigir(string $email, ?string $emailDoAluno, string $campo): void
    {
        if (! $this->usavel($email, $emailDoAluno)) {
            throw ValidationException::withMessages([$campo => self::MENSAGEM]);
        }
    }

    private function pertenceAContaDeAluno(string $email): bool
    {
        $candidatos = array_unique([mb_strtolower(trim($email)), self::normalizar($email)]);

        return User::query()->where('tipo', TipoConta::Aluno->value)
            ->whereIn(DB::raw('lower(email)'), $candidatos)->exists();
    }
}
