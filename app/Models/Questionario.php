<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'questionarios', timestamps: false)]
#[Unguarded]
class Questionario extends Model
{
    public const string TIPO_INEP = 'inep';

    /** @return BelongsTo<Assunto, $this> */
    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }

    /** @return HasMany<Questao, $this> */
    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class)->orderBy('id');
    }
}
