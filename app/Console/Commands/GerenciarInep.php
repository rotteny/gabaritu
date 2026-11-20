<?php

namespace App\Console\Commands;

use App\Domain\Conteudo\RemovedorDeInep;
use App\Models\Questao;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

#[Signature('gabaritu:inep {--relatorio : Contagem por ano, área e status} {--remover : Apaga questões, respostas e imagens do INEP} {--force : Não pede confirmação} {--limite=50 : Linhas da listagem}')]
#[Description('Lista, resume ou remove as questões de origem INEP (ENEM oficial)')]
class GerenciarInep extends Command
{
    public function handle(RemovedorDeInep $removedor): int
    {
        if ($this->option('remover')) {
            return $this->remover($removedor);
        }

        $this->option('relatorio') ? $this->relatorio() : $this->listar();

        return self::SUCCESS;
    }

    private function listar(): void
    {
        $linhas = Questao::query()->where('origem', Questao::ORIGEM_INEP)->orderBy('codigo')->limit((int) $this->option('limite'))
            ->get()->map(fn (Questao $q) => [$q->codigo, $q->origem_ref['ano'] ?? '-', $q->area, $this->status($q)]);

        $this->table(['Código', 'Ano', 'Área', 'Status'], $linhas->all());
    }

    private function relatorio(): void
    {
        $inep = Questao::query()->where('origem', Questao::ORIGEM_INEP);
        $this->line('Exibição ao aluno (GABARITU_EXIBIR_INEP): '.(config('gabaritu.exibir_inep') ? 'ligada' : 'desligada'));
        $this->line('Total: '.(clone $inep)->count());

        $this->tabela('Ano', (clone $inep)->selectRaw("origem_ref->>'ano' as chave, count(*) as total")->groupBy('chave')->orderBy('chave'));
        $this->tabela('Área', (clone $inep)->selectRaw("coalesce(area, '(sem área)') as chave, count(*) as total")->groupBy('chave')->orderBy('chave'));
        $this->tabela('Status', (clone $inep)->selectRaw($this->expressaoDeStatus().' as chave, count(*) as total')->groupBy('chave')->orderBy('chave'));
    }

    /** @param  Builder<Questao>  $consulta */
    private function tabela(string $titulo, $consulta): void
    {
        /** @var Collection<int, Questao> $linhas */
        $linhas = $consulta->get();

        $this->table([$titulo, 'Questões'], $linhas->map(fn (Questao $l) => [$l->getAttribute('chave'), $l->getAttribute('total')])->all());
    }

    /** @return literal-string */
    private function expressaoDeStatus(): string
    {
        return "case when anulada then 'anulada' when alt_pendente then 'alt pendente' when assunto_id is null then 'sem classificação' "
            ."else 'classificada, jurídico: ' || coalesce(revisao_juridica, 'pendente') end";
    }

    private function status(Questao $questao): string
    {
        return match (true) {
            $questao->anulada => 'anulada',
            $questao->alt_pendente => 'alt pendente',
            $questao->assunto_id === null => 'sem classificação',
            default => 'classificada, jurídico: '.($questao->revisao_juridica ?? 'pendente'),
        };
    }

    private function remover(RemovedorDeInep $removedor): int
    {
        $total = Questao::query()->where('origem', Questao::ORIGEM_INEP)->count();

        if (! $this->option('force') && ! $this->confirm("Apagar {$total} questões do INEP, as respostas a elas e as imagens?")) {
            $this->warn('Nada foi apagado.');

            return self::FAILURE;
        }

        $resumo = $removedor->remover(get_current_user());
        $this->info("Apagado: {$resumo['questoes']} questões, {$resumo['alternativas']} alternativas, {$resumo['respostas']} respostas, {$resumo['imagens']} imagens, ".count($resumo['arquivos']).' arquivos. Auditoria em storage/logs/auditoria.log.');

        return self::SUCCESS;
    }
}
