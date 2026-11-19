<?php

namespace App\Http\Controllers;

use App\Domain\Desempenho\ServicoDeNota;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Models\Assunto;
use App\Models\DesempenhoAssunto;
use App\Models\Materia;
use App\Models\Questionario;
use App\Models\Tentativa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    public function __construct(private readonly ServicoDeNota $nota) {}

    public function __invoke(Request $request, Materia $materia, FalaService $falas): Response
    {
        $aluno = $request->user();
        $aluno->update(['ultima_materia' => $materia->slug]);

        $assuntos = $materia->assuntos()->with('questionarios')->get();
        $desempenhos = DesempenhoAssunto::query()->where('user_id', $aluno->id)
            ->whereIn('assunto_id', $assuntos->modelKeys())->get()->keyBy('assunto_id');
        $melhores = Tentativa::query()->where('user_id', $aluno->id)->whereNotNull('finalizada_em')
            ->whereIn('questionario_id', $assuntos->pluck('questionarios')->flatten()->modelKeys())
            ->selectRaw('questionario_id, max(acertos) as melhor')->groupBy('questionario_id')
            ->pluck('melhor', 'questionario_id');

        return Inertia::render('Materia', [
            'materia' => ['slug' => $materia->slug, 'nome' => $materia->nome],
            'assuntos' => $assuntos->map(fn (Assunto $assunto) => $this->assunto($assunto, $desempenhos, $melhores))->all(),
            'fala' => $falas->sortear($aluno, ['materia'], Fala::HUMOR_NEUTRO),
        ]);
    }

    /**
     * @param  Collection<int, DesempenhoAssunto>  $desempenhos
     * @param  Collection<int, mixed>  $melhores
     * @return array<string, mixed>
     */
    private function assunto(Assunto $assunto, Collection $desempenhos, Collection $melhores): array
    {
        return [
            'slug' => $assunto->slug,
            'nome' => $assunto->nome,
            ...$this->nota->resumo($desempenhos->get($assunto->id)),
            'questionarios' => $assunto->questionarios->map(fn (Questionario $q) => [
                'id' => $q->id,
                'titulo' => $q->titulo,
                'ordem' => $q->ordem,
                'feito' => $melhores->has($q->id),
                'melhor_acertos' => $melhores->has($q->id) ? (int) $melhores->get($q->id) : null,
            ])->all(),
        ];
    }
}
