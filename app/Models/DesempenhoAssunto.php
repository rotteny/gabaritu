<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'desempenhos_assunto', timestamps: false)]
#[Unguarded]
class DesempenhoAssunto extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['calculado_em' => 'datetime', 'theta' => 'float'];
    }

    /** @return BelongsTo<Assunto, $this> */
    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }
}
