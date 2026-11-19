<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'alternativas', timestamps: false)]
#[Unguarded]
class Alternativa extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['correta' => 'boolean'];
    }

    /** @return BelongsTo<Questao, $this> */
    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    /** @return BelongsTo<Etiqueta, $this> */
    public function etiqueta(): BelongsTo
    {
        return $this->belongsTo(Etiqueta::class);
    }
}
