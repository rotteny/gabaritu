<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'tentativa_questoes', timestamps: false)]
#[Unguarded]
#[DateFormat('Y-m-d H:i:s.v')]
class TentativaQuestao extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['servida_em' => 'datetime'];
    }

    /** @return BelongsTo<Tentativa, $this> */
    public function tentativa(): BelongsTo
    {
        return $this->belongsTo(Tentativa::class);
    }

    /** @return BelongsTo<Questao, $this> */
    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }
}
