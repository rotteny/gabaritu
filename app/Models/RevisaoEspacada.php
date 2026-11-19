<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'revisoes_espacadas', timestamps: false)]
#[Unguarded]
class RevisaoEspacada extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['proxima_em' => 'date'];
    }

    /** @return BelongsTo<Questao, $this> */
    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }
}
