<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Prova permanente do aceite (e da aprovação, nos pedidos) de um convite usado. Não cascateia com o convite.
 *
 * @property int $id
 * @property int|null $convite_id
 * @property string $tipo
 * @property int|null $aluno_id
 * @property int|null $responsavel_id
 * @property int|null $aprovado_por_id
 * @property Carbon $aceito_em
 * @property string|null $aceite_ip
 * @property string $termos_versao
 * @property string $privacidade_versao
 * @property Carbon|null $aluno_aceitou_em
 * @property Carbon|null $aprovado_em
 */
#[Table('aceites_responsavel')]
class AceiteDeResponsavel extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['aceito_em' => 'datetime', 'aluno_aceitou_em' => 'datetime', 'aprovado_em' => 'datetime'];
    }
}
