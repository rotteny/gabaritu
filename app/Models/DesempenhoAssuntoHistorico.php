<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'desempenho_assunto_historico', timestamps: false)]
#[Unguarded]
class DesempenhoAssuntoHistorico extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['calculado_em' => 'datetime', 'theta' => 'float'];
    }
}
