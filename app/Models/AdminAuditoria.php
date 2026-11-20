<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Trilha de auditoria do painel. Só se grava: não há update nem delete pelo app.
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string $admin_email
 * @property string $acao
 * @property string|null $alvo_tipo
 * @property int|null $alvo_id
 * @property string|null $motivo
 * @property array<string, mixed>|null $detalhes
 * @property Carbon $criado_em
 */
#[Table(name: 'admin_auditoria', timestamps: false)]
#[Unguarded]
class AdminAuditoria extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['detalhes' => 'array', 'criado_em' => 'datetime'];
    }

    protected static function booted(): void
    {
        $recusar = fn () => throw new LogicException('A auditoria do painel é somente de inclusão.');
        static::updating($recusar);
        static::deleting($recusar);
    }
}
