<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'respostas', timestamps: false)]
#[Unguarded]
#[DateFormat('Y-m-d H:i:s.v')]
class Resposta extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'correta' => 'boolean',
            'chute' => 'boolean',
            'repetida' => 'boolean',
            'servida_em' => 'datetime',
            'respondida_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Questao, $this> */
    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    /** @return BelongsTo<Alternativa, $this> */
    public function alternativa(): BelongsTo
    {
        return $this->belongsTo(Alternativa::class);
    }

    /** @return BelongsTo<Tentativa, $this> */
    public function tentativa(): BelongsTo
    {
        return $this->belongsTo(Tentativa::class);
    }
}
