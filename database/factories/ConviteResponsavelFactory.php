<?php

namespace Database\Factories;

use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ConviteResponsavel> */
class ConviteResponsavelFactory extends Factory
{
    /** O token em claro fica em `$token` do estado: use `comToken()` nos testes. */
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'aluno_id' => User::factory()->aguardandoResponsavel(),
            'tipo' => 'autorizacao',
            'email' => fake()->unique()->safeEmail(),
            'parentesco' => 'mae',
            'token_hash' => ConviteResponsavel::hashDoToken(Str::random(48)),
            'expira_em' => now()->addDays(7),
        ];
    }

    public function comToken(string $token): static
    {
        return $this->state(fn () => ['token_hash' => ConviteResponsavel::hashDoToken($token)]);
    }

    public function vencido(): static
    {
        return $this->state(fn () => ['expira_em' => now()->subMinute()]);
    }
}
