<?php

namespace App\Models;

use App\Enums\Nivel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $apelido
 * @property string $email
 * @property int|null $ano_nascimento
 * @property string|null $serie
 * @property string $zoeira
 * @property bool $som
 * @property bool $musica
 * @property bool $reduzir_movimento
 * @property string|null $ultima_materia
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['apelido', 'email', 'password', 'ano_nascimento', 'serie', 'zoeira', 'som', 'musica', 'reduzir_movimento', 'ultima_materia'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'som' => 'boolean',
            'musica' => 'boolean',
            'reduzir_movimento' => 'boolean',
            'ano_nascimento' => 'integer',
        ];
    }

    public static function idadePorAno(int $anoNascimento): int
    {
        return (int) now(config('gabaritu.fuso'))->format('Y') - $anoNascimento - 1;
    }

    public function idade(): int
    {
        return self::idadePorAno((int) $this->ano_nascimento);
    }

    public function zoeiraMaxima(): Nivel
    {
        return Nivel::tetoParaIdade($this->idade());
    }

    public function zoeiraEfetiva(): Nivel
    {
        return (Nivel::tryFrom($this->zoeira) ?? Nivel::Fofo)->limitadoPor($this->zoeiraMaxima());
    }

    /** @return array<string, mixed> */
    public function paraCompartilhar(): array
    {
        return [
            'id' => $this->id,
            'apelido' => $this->apelido,
            'serie' => $this->serie,
            'idade' => $this->idade(),
            'preferencias' => [
                'zoeira' => $this->zoeiraEfetiva()->value,
                'zoeira_maxima' => $this->zoeiraMaxima()->value,
                'som' => $this->som,
                'musica' => $this->musica,
                'reduzir_movimento' => $this->reduzir_movimento,
                'ultima_materia' => $this->ultima_materia,
            ],
        ];
    }

    /** @return HasMany<Tentativa, $this> */
    public function tentativas(): HasMany
    {
        return $this->hasMany(Tentativa::class);
    }

    /** @return HasMany<Resposta, $this> */
    public function respostas(): HasMany
    {
        return $this->hasMany(Resposta::class);
    }
}
