<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'chaves_fala', timestamps: false)]
#[Unguarded]
class ChaveFala extends Model
{
    /** @return HasMany<VariacaoFala, $this> */
    public function variacoes(): HasMany
    {
        return $this->hasMany(VariacaoFala::class);
    }
}
