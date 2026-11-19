<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'feedbacks_fala', timestamps: false)]
#[Unguarded]
class FeedbackFala extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['legal' => 'boolean'];
    }

    /** @return BelongsTo<VariacaoFala, $this> */
    public function variacao(): BelongsTo
    {
        return $this->belongsTo(VariacaoFala::class, 'variacao_fala_id');
    }
}
