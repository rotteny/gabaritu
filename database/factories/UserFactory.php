<?php

namespace Database\Factories;

use App\Models\User;
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
}
