<?php

namespace App\Actions\Fortify;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use ProfileValidationRules;

    private const string ZOEIRA_INICIAL = 'fofo';

    /** @param  array<string, mixed>  $input */
    public function create(array $input): User
    {
        $dados = Validator::make($input, [
            'apelido' => $this->apelidoRules(),
            'email' => $this->emailRules(),
            'senha' => $this->senhaRules(),
            'ano_nascimento' => $this->anoNascimentoRules(),
            'serie' => $this->serieRules(),
            'aceite_privacidade' => ['accepted'],
        ], [
            'aceite_privacidade.accepted' => 'Para criar a conta, é preciso aceitar a política de privacidade.',
        ])->validate();

        $aluno = new User([
            'apelido' => $dados['apelido'],
            'email' => $dados['email'],
            'password' => $dados['senha'],
            'ano_nascimento' => (int) $dados['ano_nascimento'],
            'serie' => $dados['serie'],
            'zoeira' => self::ZOEIRA_INICIAL,
        ]);
        // Fora do fillable de propósito: só o cadastro grava o aceite.
        $aluno->forceFill([
            'privacidade_aceita_em' => now(),
            'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
        ])->save();

        return $aluno;
    }

    /** @return array<int, mixed> */
    private function senhaRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
