<?php

namespace App\Console\Commands;

use App\Domain\Conteudo\ImportadorConteudo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gabaritu:importar-conteudo {--materias= : Pasta das matérias (padrão: conteudo/materias)} {--interface= : Arquivo de falas de interface}')]
#[Description('Importa matérias, questões, etiquetas e falas de forma idempotente')]
class ImportarConteudo extends Command
{
    public function handle(ImportadorConteudo $importador): int
    {
        $materias = (string) ($this->option('materias') ?: base_path((string) config('gabaritu.conteudo_path')));
        $interface = (string) ($this->option('interface') ?: base_path((string) config('gabaritu.falas.interface')));

        $resumo = $importador->importar($materias, $interface);

        foreach ($resumo as $nome => $total) {
            $this->line("{$nome}: {$total}");
        }

        return self::SUCCESS;
    }
}
