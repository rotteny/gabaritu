<?php

namespace App\Domain\Desempenho;

use App\Domain\Diagnostico\Diagnostico;
use App\Models\Assunto;
use App\Models\DesempenhoAssunto;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Etiqueta;
use App\Models\Materia;
use App\Models\Resposta;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PainelDeDesempenho
{
    public function __construct(
        private readonly ServicoDeNota $nota,
        private readonly Diagnostico $diagnostico,
    ) {}

    /** @return list<array<string, mixed>> */
    public function montar(User $aluno): array
    {
        $materiaIds = $this->materiasComRespostas($aluno);

        if ($materiaIds === []) {
            return [];
        }

        $desempenhos = DesempenhoAssunto::query()->where('user_id', $aluno->id)->get()->keyBy('assunto_id');
        $evolucao = $this->evolucao($aluno);

        return Materia::query()->whereIn('id', $materiaIds)->with('assuntos')->orderBy('ordem')->get()
            ->map(fn (Materia $materia) => [
                'slug' => $materia->slug,
                'nome' => $materia->nome,
                'nota' => $this->nota->notaDaMateria($doMateria = $materia->assuntos->map(fn (Assunto $a) => $desempenhos->get($a->id))->filter()),
                'amostra_baixa' => $this->nota->amostraBaixaDaMateria($doMateria),
                ...$this->diagnosticar($aluno, 'assuntos.materia_id', $materia->id),
                'assuntos' => $materia->assuntos->map(fn (Assunto $assunto) => [
                    'slug' => $assunto->slug,
                    'nome' => $assunto->nome,
                    ...$this->nota->resumo($desempenhos->get($assunto->id)),
                    'evolucao' => $evolucao->get($assunto->id, collect())->values()->all(),
                    ...$this->diagnosticar($aluno, 'assuntos.id', $assunto->id),
                ])->all(),
            ])->values()->all();
    }

    /** @return list<int> */
    private function materiasComRespostas(User $aluno): array
    {
        return Resposta::query()
            ->where('respostas.user_id', $aluno->id)
            ->join('questoes', 'questoes.id', '=', 'respostas.questao_id')
            ->join('assuntos', 'assuntos.id', '=', 'questoes.assunto_id')
            ->distinct()
            ->pluck('assuntos.materia_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return Collection<int, Collection<int, array{data: string, nota: int}>> */
    private function evolucao(User $aluno): Collection
    {
        return DesempenhoAssuntoHistorico::query()
            ->where('user_id', $aluno->id)
            ->orderBy('id')
            ->get()
            ->groupBy('assunto_id')
            ->map(fn (Collection $snapshots) => $snapshots
                ->groupBy(fn (DesempenhoAssuntoHistorico $s) => $s->calculado_em->setTimezone(config('gabaritu.fuso'))->toDateString())
                ->map(fn (Collection $doDia) => $doDia->last())
                ->map(fn (DesempenhoAssuntoHistorico $s, string $data) => [
                    'data' => $data,
                    'nota' => $this->nota->exibidaDeSnapshot($s->nota, $s->n_primeiras),
                ])
                ->filter(fn (array $ponto) => $ponto['nota'] !== null));
    }

    /** @return array{diagnostico: list<array{etiqueta: string, nome: string, percentual: int}>, diagnostico_erros: int} */
    private function diagnosticar(User $aluno, string $coluna, int $id): array
    {
        $janela = Resposta::query()
            ->where('respostas.user_id', $aluno->id)
            ->where('respostas.chute', false)
            ->join('questoes', 'questoes.id', '=', 'respostas.questao_id')
            ->join('assuntos', 'assuntos.id', '=', 'questoes.assunto_id')
            ->join('alternativas', 'alternativas.id', '=', 'respostas.alternativa_id')
            ->where('questoes.status', 'ativa')
            ->where($coluna, $id)
            ->orderByDesc('respostas.id')
            ->limit((int) config('gabaritu.diagnostico.janela'))
            ->get(['respostas.id as resposta_id', 'respostas.correta', 'alternativas.etiqueta_id']);

        $erros = $janela->where('correta', false)
            ->map(fn ($linha) => ['etiqueta_id' => $linha->etiqueta_id === null ? null : (int) $linha->etiqueta_id, 'ordem' => (int) $linha->resposta_id])
            ->values()->all();

        $itens = $this->diagnostico->calcular($erros);
        $etiquetas = Etiqueta::query()->whereIn('id', array_map(fn ($i) => $i->etiquetaId, $itens))->get()->keyBy('id');

        return [
            'diagnostico' => array_map(fn ($item) => [
                'etiqueta' => $etiquetas[$item->etiquetaId]->codigo,
                'nome' => $etiquetas[$item->etiquetaId]->nome,
                'percentual' => $item->percentual,
            ], $itens),
            'diagnostico_erros' => count($erros),
        ];
    }
}
