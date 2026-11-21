<?php

namespace App\Domain\Tentativa;

use App\Models\Tentativa;

class FinalizadorDeTentativa
{
    public function __construct(private readonly ApresentadorDeQuestao $apresentador) {}

    public function finalizarSeCompleta(Tentativa $tentativa): bool
    {
        if ($this->apresentador->temItemPendente($tentativa)) {
            return false;
        }

        if ($tentativa->finalizada_em === null) {
            $tentativa->update([
                'finalizada_em' => now(),
                'acertos' => $tentativa->respostas()->where('correta', true)->count(),
            ]);
        }

        return true;
    }
}
