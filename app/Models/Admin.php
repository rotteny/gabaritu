<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Conta do painel admin: separada de `users`, criada e trocada só por comando (gabaritu:admin).
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $dois_fatores_segredo
 * @property list<string>|null $codigos_recuperacao hashes HMAC, nunca o código em claro
 * @property int|null $ultimo_passo_totp
 * @property Carbon|null $confirmado_em
 * @property Carbon|null $ultimo_login_em
 */
#[Table('admins')]
#[Fillable(['email', 'password'])]
#[Hidden(['password', 'dois_fatores_segredo', 'codigos_recuperacao'])]
class Admin extends Authenticatable
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'dois_fatores_segredo' => 'encrypted',
            'codigos_recuperacao' => 'array',
            'confirmado_em' => 'datetime',
            'ultimo_login_em' => 'datetime',
        ];
    }

    public function temDoisFatoresConfirmado(): bool
    {
        return $this->confirmado_em !== null && $this->dois_fatores_segredo !== null;
    }

    /** Sem "lembrar de mim": o admin nunca fica logado fora da sessão curta. */
    public function getRememberTokenName(): string
    {
        return '';
    }
}
