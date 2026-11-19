<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'materias', timestamps: false)]
#[Unguarded]
class Materia extends Model
{
    /** @return HasMany<Assunto, $this> */
    public function assuntos(): HasMany
    {
        return $this->hasMany(Assunto::class)->orderBy('ordem');
    }

    /** @return HasMany<Etiqueta, $this> */
    public function etiquetas(): HasMany
    {
        return $this->hasMany(Etiqueta::class);
    }
}
