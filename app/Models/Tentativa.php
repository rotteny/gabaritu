<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'tentativas', timestamps: false)]
#[Unguarded]
class Tentativa extends Model
{
    public const string TIPO_QUESTIONARIO = 'questionario';

    public const string TIPO_REVISAO = 'revisao';

    public const string TIPO_REVISAO_ERRADAS = 'revisao_erradas';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['iniciada_em' => 'datetime', 'finalizada_em' => 'datetime'];
    }

    public function ehRevisao(): bool
    {
        return $this->tipo !== self::TIPO_QUESTIONARIO;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Questionario, $this> */
    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class);
    }

    /** @return BelongsTo<Assunto, $this> */
    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }

    /** @return HasMany<TentativaQuestao, $this> */
    public function itens(): HasMany
    {
        return $this->hasMany(TentativaQuestao::class)->orderBy('posicao');
    }

    /** @return HasMany<Resposta, $this> */
    public function respostas(): HasMany
    {
        return $this->hasMany(Resposta::class);
    }
}
