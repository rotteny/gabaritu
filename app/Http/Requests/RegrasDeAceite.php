<?php

namespace App\Http\Requests;

use App\Concerns\ParentescoValidationRules;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

/** Regras do aceite do convite: o responsável novo informa mais campos que o que já tem conta. */
class RegrasDeAceite
{
    use ParentescoValidationRules;

    private const int IDADE_MAXIMA_ACEITA = 100;

    private const int NOME_MAX = 80;

    /** @return array<string, array<int, mixed>> */
    public function regras(bool $contaNova): array
    {
        return [
            ...$this->parentescoRules(),
            'declara_responsavel' => ['accepted'],
            'aceite_termos' => ['accepted'],
            ...($contaNova ? $this->regrasDaContaNova() : []),
        ];
    }

    /** @return array<string, string> */
    public function mensagens(): array
    {
        return [
            'declara_responsavel.accepted' => 'Para autorizar, confirme que você é responsável legal pelo aluno.',
            'aceite_termos.accepted' => 'Para continuar, aceite os Termos e a Política de Privacidade.',
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function regrasDaContaNova(): array
    {
        $anoAtual = (int) now(config('gabaritu.fuso'))->format('Y');

        return [
            'nome' => ['required', 'string', 'min:2', 'max:'.self::NOME_MAX],
            'senha' => ['required', 'string', Password::default(), 'confirmed'],
            'ano_nascimento' => [
                'required', 'integer', 'min:'.($anoAtual - self::IDADE_MAXIMA_ACEITA),
                function (string $atributo, mixed $valor, \Closure $falhar): void {
                    if (is_numeric($valor) && User::idadePorAno((int) $valor) < User::IDADE_MAIORIDADE) {
                        $falhar('O responsável precisa ter 18 anos ou mais.');
                    }
                },
            ],
        ];
    }
}
