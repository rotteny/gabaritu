<?php

namespace App\Models;

use App\Models\Concerns\TemImagem;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $imagem_url
 * @property string|null $imagem_alt
 * @property int|null $imagem_largura
 * @property int|null $imagem_altura
 * @property string $origem
 * @property array<string, mixed>|null $origem_ref
 * @property string|null $revisao_juridica
 * @property int|null $assunto_id
 */
#[Table(name: 'questoes', timestamps: false)]
#[Unguarded]
class Questao extends Model
{
    use TemImagem;

    public const string ORIGEM_AUTORAL = 'autoral';

    public const string ORIGEM_INEP = 'inep';

    public const string JURIDICO_REMOVER = 'remover';

    public const string STATUS_ATIVA = 'ativa';

    public const string STATUS_ANULADA = 'anulada';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'anulada_em' => 'datetime',
            'origem_ref' => 'array',
            'alt_pendente' => 'boolean',
            'anulada' => 'boolean',
        ];
    }

    /**
     * Só o que o aluno pode ver: ativa, classificada, com alt completo e, se for INEP, com a flag ligada e sem ordem de remoção.
     *
     * @param  Builder<Questao>  $query
     */
    #[Scope]
    protected function servivel(Builder $query): void
    {
        $query->where('questoes.status', self::STATUS_ATIVA)
            ->where('questoes.anulada', false)
            ->where('questoes.alt_pendente', false)
            ->whereNotNull('questoes.assunto_id')
            ->where(function (Builder $origem): void {
                $origem->where('questoes.origem', '!=', self::ORIGEM_INEP);
                if (config('gabaritu.exibir_inep')) {
                    $origem->orWhere(fn (Builder $inep) => $inep
                        ->where('questoes.origem', self::ORIGEM_INEP)
                        ->where(fn (Builder $juridico) => $juridico
                            ->whereNull('questoes.revisao_juridica')
                            ->orWhere('questoes.revisao_juridica', '!=', self::JURIDICO_REMOVER)));
                }
            });
    }

    public function ehInep(): bool
    {
        return $this->origem === self::ORIGEM_INEP;
    }

    public function fonteParaProps(): ?string
    {
        $ano = $this->origem_ref['ano'] ?? null;

        return $this->ehInep() && $ano !== null ? "ENEM {$ano}, INEP/MEC" : null;
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
