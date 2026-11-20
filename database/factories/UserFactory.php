<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'apelido' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'ano_nascimento' => (int) now()->format('Y') - 17,
            'serie' => '2em',
            'zoeira' => 'sarc',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function comIdade(int $idade): static
    {
        return $this->state(fn () => ['ano_nascimento' => (int) now()->format('Y') - $idade - 1]);
    }

    public function comZoeira(string $nivel): static
    {
        return $this->state(fn () => ['zoeira' => $nivel]);
    }

    public function responsavel(): static
    {
        return $this->state(fn () => [
            'tipo' => 'responsavel',
            'serie' => null,
            'zoeira' => 'fofo',
            'ano_nascimento' => (int) now()->format('Y') - 40,
        ]);
    }

    public function aguardandoResponsavel(?string $emailDoResponsavel = null): static
    {
        return $this->state(fn () => [
            'status' => 'aguardando_responsavel',
            'email_responsavel' => $emailDoResponsavel ?? fake()->unique()->safeEmail(),
            'parentesco_responsavel' => 'mae',
            'pendente_desde' => now(),
        ]);
    }

    public function pendenteHaDias(int $dias): static
    {
        return $this->aguardandoResponsavel()->state(fn () => ['pendente_desde' => now()->subDays($dias)]);
    }

    /** Aluno com um vínculo ativo, como exige a invariante do menor ativo. */
    public function comResponsavel(?User $responsavel = null, string $parentesco = 'mae'): static
    {
        return $this->afterCreating(function (User $aluno) use ($responsavel, $parentesco): void {
            VinculoResponsavel::factory()->create([
                'aluno_id' => $aluno->id,
                'responsavel_id' => ($responsavel ?? User::factory()->responsavel()->create())->id,
                'parentesco' => $parentesco,
            ]);
        });
    }
}
