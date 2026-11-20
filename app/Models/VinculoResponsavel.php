<?php

namespace App\Models;

use Database\Factories\VinculoResponsavelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Vínculo ativo entre aluno e responsável, com o registro do aceite (prova da autorização).
 *
 * @property int $id
 * @property int $aluno_id
 * @property int $responsavel_id
 * @property string $parentesco
 * @property string|null $parentesco_outro
 * @property Carbon $aceito_em
 * @property string $termos_versao
 * @property string $privacidade_versao
 * @property string|null $aceite_ip
 * @property bool $declara_responsavel
 * @property bool $aceite_termos
 */
#[Table('vinculos_responsavel')]
#[Fillable(['aluno_id', 'responsavel_id', 'parentesco', 'parentesco_outro', 'aceito_em', 'termos_versao', 'privacidade_versao', 'aceite_ip', 'declara_responsavel', 'aceite_termos'])]
class VinculoResponsavel extends Model
{
    /** @use HasFactory<VinculoResponsavelFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['aceito_em' => 'datetime', 'declara_responsavel' => 'boolean', 'aceite_termos' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    /** @return BelongsTo<User, $this> */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
