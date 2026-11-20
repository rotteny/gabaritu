<?php

namespace App\Console\Commands;

use App\Domain\Conteudo\ImportacaoRecusada;
use App\Domain\Conteudo\ImportadorConteudo;
use App\Domain\Conteudo\ImportadorInep;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gabaritu:importar-conteudo {--materias= : Pasta das matérias (padrão: conteudo/materias)} {--interface= : Arquivo de falas de interface} {--fonte-externa= : Pasta com os JSON do INEP (fora do repositório)}')]
#[Description('Importa matérias, questões, etiquetas e falas de forma idempotente; com --fonte-externa, também as questões do INEP')]
class ImportarConteudo extends Command
{
    public function handle(ImportadorConteudo $importador, ImportadorInep $inep): int
    {
        $materias = (string) ($this->option('materias') ?: base_path((string) config('gabaritu.conteudo_path')));
        $interface = (string) ($this->option('interface') ?: base_path((string) config('gabaritu.falas.interface')));

        try {
            $resumo = $importador->importar($materias, $interface);

            if ($this->option('fonte-externa')) {
                foreach ($inep->importar((string) $this->option('fonte-externa')) as $nome => $total) {
                    $resumo["inep_{$nome}"] = $total;
                }
            }
        } catch (ImportacaoRecusada $e) {
            foreach ($e->erros as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        foreach ($resumo as $nome => $total) {
            $this->line("{$nome}: {$total}");
        }

        return self::SUCCESS;
    }
}
