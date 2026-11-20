<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'assuntos', timestamps: false)]
#[Unguarded]
class Assunto extends Model
{
    /**
     * Só o assunto que o aluno consegue praticar: tem ao menos um questionário com questão servível.
     *
     * @param  Builder<Assunto>  $query
     */
    #[Scope]
    protected function servivel(Builder $query): void
    {
        $query->whereHas('questionarios', fn (Builder $questionarios) => $questionarios
            ->whereHas('questoes', fn (Builder $questoes) => $questoes->servivel()));
    }

    /** @return BelongsTo<Materia, $this> */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    /** @return HasMany<Questionario, $this> */
    public function questionarios(): HasMany
    {
        return $this->hasMany(Questionario::class)->orderBy('ordem');
    }

    /** @return HasMany<Questao, $this> */
    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class);
    }
}
