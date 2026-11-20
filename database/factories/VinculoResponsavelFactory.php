<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VinculoResponsavel> */
class VinculoResponsavelFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'aluno_id' => User::factory(),
            'responsavel_id' => User::factory()->responsavel(),
            'parentesco' => 'mae',
            'aceito_em' => now(),
            'termos_versao' => (string) config('gabaritu.termos_versao'),
            'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
            'aceite_ip' => '127.0.0.1',
            'declara_responsavel' => true,
            'aceite_termos' => true,
        ];
    }
}
