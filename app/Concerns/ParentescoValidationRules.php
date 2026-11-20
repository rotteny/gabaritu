<?php

namespace App\Concerns;

use App\Enums\Parentesco;
use Illuminate\Validation\Rule;

trait ParentescoValidationRules
{
    private const int PARENTESCO_OUTRO_MAX = 40;

    /** @return array<string, array<int, mixed>> */
    protected function parentescoRules(string $campo = 'parentesco', string $campoOutro = 'parentesco_outro'): array
    {
        return [
            $campo => ['required', Rule::enum(Parentesco::class)],
            $campoOutro => ["required_if:{$campo},".Parentesco::Outro->value, 'nullable', 'string', 'max:'.self::PARENTESCO_OUTRO_MAX],
        ];
    }
}
