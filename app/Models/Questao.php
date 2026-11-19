<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'questoes', timestamps: false)]
#[Unguarded]
class Questao extends Model
{
    public const string STATUS_ATIVA = 'ativa';

    public const string STATUS_ANULADA = 'anulada';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['anulada_em' => 'datetime'];
    }

    /** @return BelongsTo<Assunto, $this> */
    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }

    /** @return BelongsTo<Questionario, $this> */
    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class);
    }

    /** @return HasMany<Alternativa, $this> */
    public function alternativas(): HasMany
    {
        return $this->hasMany(Alternativa::class)->orderBy('letra');
    }
}
