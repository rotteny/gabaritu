<?php

namespace App\Console\Commands;

use App\Domain\Responsavel\LimpezaDePendentes;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gabaritu:limpar-pendentes')]
#[Description('Remove pendentes há mais de 30 dias (com aviso), convites vencidos e responsáveis sem aluno.')]
class LimparPendentes extends Command
{
    public function handle(LimpezaDePendentes $limpeza): int
    {
        $resultado = $limpeza->executar();

        $this->info(sprintf(
            'Avisados: %d | alunos removidos: %d | convites vencidos: %d | responsáveis removidos: %d',
            $resultado['avisados'],
            $resultado['removidos'],
            $resultado['convites'],
            $resultado['responsaveis'],
        ));

        return self::SUCCESS;
    }
}
