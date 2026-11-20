<?php

namespace App\Concerns;

use App\Enums\Serie;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    private const int APELIDO_MAX = 30;

    private const int APELIDO_MIN = 2;

    private const int IDADE_MAXIMA_ACEITA = 100;

    /** @return array<int, string> */
    protected function apelidoRules(): array
    {
        return ['required', 'string', 'min:'.self::APELIDO_MIN, 'max:'.self::APELIDO_MAX];
    }

    /** @return array<int, Enum|string> */
    protected function serieRules(): array
    {
        return ['required', Rule::enum(Serie::class)];
    }

    /** @return array<int, \Closure|Unique|string> */
    protected function emailRules(): array
    {
        return ['required', 'string', 'email', 'max:255', Rule::unique(User::class), function (string $atributo, mixed $valor, \Closure $falhar): void {
            if (is_string($valor) && User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($valor))])->exists()) {
                $falhar(__('validation.unique', ['attribute' => $atributo]));
            }
        }];
    }

    /** @return array<int, \Closure|ValidationRule|string> */
    protected function anoNascimentoRules(): array
    {
        $anoAtual = (int) now(config('gabaritu.fuso'))->format('Y');

        return [
            'required',
            'integer',
            'min:'.($anoAtual - self::IDADE_MAXIMA_ACEITA),
            function (string $atributo, mixed $valor, \Closure $falhar): void {
                if (is_numeric($valor) && User::idadePorAno((int) $valor) < (int) config('gabaritu.idade_minima')) {
                    $falhar('O Gabaritu é para quem tem 13 anos ou mais. Não conseguimos criar a sua conta.');
                }
            },
        ];
    }
}
