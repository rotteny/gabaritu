<?php

namespace App\Domain\Desempenho;

use App\Domain\Diagnostico\Diagnostico;
use App\Models\Assunto;
use App\Models\DesempenhoAssunto;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Etiqueta;
use App\Models\Materia;
use App\Models\Questao;
use App\Models\Resposta;
use App\Models\User;
use Illuminate\Support\Collection;

class PainelDeDesempenho
{
    public function __construct(
        private readonly ServicoDeNota $nota,
        private readonly Diagnostico $diagnostico,
    ) {}

    /** @return array<int, array<string, mixed>> */
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
                'evolucao' => $this->evolucaoDaMateria($materia, $evolucao),
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

    /**
     * @param  Collection<int|string, Collection<int|string, array{data: int|string, nota: int}>>  $evolucao
     * @return array<int, array{data: int|string, nota: int}>
     */
    private function evolucaoDaMateria(Materia $materia, Collection $evolucao): array
    {
        return $materia->assuntos
            ->flatMap(fn (Assunto $assunto) => $evolucao->get($assunto->id, collect())->values())
            ->groupBy('data')
            ->map(fn (Collection $doDia, $data) => ['data' => $data, 'nota' => (int) (round($doDia->avg('nota') / 10) * 10)])
            ->sortKeys()
            ->values()
            ->all();
    }

    /** @return array<int, int> */
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

    /** @return Collection<int|string, Collection<int|string, array{data: int|string, nota: int}>> */
    private function evolucao(User $aluno): Collection
    {
        return DesempenhoAssuntoHistorico::query()
            ->where('user_id', $aluno->id)
            ->orderBy('id')
            ->get()
            ->groupBy('assunto_id')
            ->map(fn (Collection $snapshots) => $snapshots
                ->groupBy(fn (DesempenhoAssuntoHistorico $s) => $s->calculado_em->copy()->setTimezone((string) config('gabaritu.fuso'))->toDateString())
                ->map(fn (Collection $doDia) => $doDia->last())
                ->map(fn ($s, $data) => [
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
            ->whereIn('questoes.id', Questao::query()->servivel()->select('questoes.id'))
            ->where($coluna, $id)
            ->orderByDesc('respostas.id')
            ->limit((int) config('gabaritu.diagnostico.janela'))
            ->get(['respostas.id as resposta_id', 'respostas.correta', 'alternativas.etiqueta_id']);

        $erros = [];
        foreach ($janela as $linha) {
            $dados = $linha->getAttributes();
            if (! $dados['correta']) {
                $erros[] = ['etiqueta_id' => $dados['etiqueta_id'] === null ? null : (int) $dados['etiqueta_id'], 'ordem' => (int) $dados['resposta_id']];
            }
        }

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
