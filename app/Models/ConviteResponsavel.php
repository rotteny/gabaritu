<?php

namespace App\Models;

use App\Enums\TipoConvite;
use Database\Factories\ConviteResponsavelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Convite de uso único. Só o hash do token é guardado; o token em claro vai apenas no e-mail.
 *
 * @property int $id
 * @property int $aluno_id
 * @property int|null $solicitante_id
 * @property int|null $substitui_responsavel_id
 * @property string $tipo
 * @property string $email
 * @property string $parentesco
 * @property string|null $parentesco_outro
 * @property string $token_hash
 * @property Carbon $expira_em
 * @property Carbon|null $usado_em
 * @property Carbon|null $recusado_em
 * @property Carbon|null $invalidado_em
 * @property string|null $aceite_ip
 * @property Carbon|null $aceite_em
 * @property string|null $termos_versao
 * @property bool $exige_aprovacao
 * @property int|null $aprovado_por_id
 * @property Carbon|null $aprovado_em
 * @property Carbon|null $aluno_aceitou_em
 * @property Carbon|null $created_at
 */
#[Table('convites_responsavel')]
#[Fillable(['aluno_id', 'solicitante_id', 'substitui_responsavel_id', 'tipo', 'email', 'parentesco', 'parentesco_outro', 'token_hash', 'expira_em', 'aceite_ip', 'aceite_em', 'termos_versao', 'exige_aprovacao', 'aprovado_por_id'])]
class ConviteResponsavel extends Model
{
    /** @use HasFactory<ConviteResponsavelFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['aceite_em' => 'datetime', 'aprovado_em' => 'datetime', 'aluno_aceitou_em' => 'datetime', 'exige_aprovacao' => 'boolean', 'expira_em' => 'datetime', 'usado_em' => 'datetime', 'recusado_em' => 'datetime', 'invalidado_em' => 'datetime'];
    }

    public static function hashDoToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @param  Builder<ConviteResponsavel>  $consulta */
    public function scopePendentes(Builder $consulta): void
    {
        $consulta->whereNull('usado_em')->whereNull('recusado_em')->whereNull('invalidado_em')->where('expira_em', '>', now());
    }

    /** @param  Builder<ConviteResponsavel>  $consulta */
    public function scopeParaResponsavel(Builder $consulta): void
    {
        $consulta->whereIn('tipo', TipoConvite::paraResponsavel());
    }

    /** @param  Builder<ConviteResponsavel>  $consulta */
    public function scopePedidos(Builder $consulta): void
    {
        $consulta->whereIn('tipo', TipoConvite::pedidos());
    }

    public function estaPendente(): bool
    {
        return $this->usado_em === null && $this->recusado_em === null
            && $this->invalidado_em === null && $this->expira_em->isFuture();
    }

    /** @return BelongsTo<User, $this> */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /** @return BelongsTo<User, $this> */
    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }
}
