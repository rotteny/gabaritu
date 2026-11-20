<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Erro apontado por um aluno numa questão.
 *
 * @property int $id
 * @property int $questao_id
 * @property int $user_id
 * @property string $motivo
 * @property string|null $comentario
 * @property string $status
 * @property Carbon|null $resolvido_em
 * @property Carbon|null $created_at
 */
#[Table('reports_questao')]
#[Unguarded]
class ReportQuestao extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['resolvido_em' => 'datetime'];
    }

    /** @return BelongsTo<Questao, $this> */
    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    /** @return BelongsTo<User, $this> */
    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
