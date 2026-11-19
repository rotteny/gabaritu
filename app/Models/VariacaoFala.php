<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'variacoes_fala', timestamps: false)]
#[Unguarded]
class VariacaoFala extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reserva' => 'boolean',
            'inicio_em' => 'date',
            'validade_em' => 'date',
        ];
    }

    /** @return BelongsTo<ChaveFala, $this> */
    public function chave(): BelongsTo
    {
        return $this->belongsTo(ChaveFala::class, 'chave_fala_id');
    }
}
