<?php

namespace App\Http\Controllers;

use App\Domain\Desempenho\ServicoDeNota;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Revisao\FilaDeRevisao;
use App\Models\DesempenhoAssunto;
use App\Models\Materia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InicioController extends Controller
{
    public function __invoke(Request $request, FalaService $falas, ServicoDeNota $nota, FilaDeRevisao $fila): Response
    {
        $aluno = $request->user();
        $desempenhos = DesempenhoAssunto::query()->where('user_id', $aluno->id)->whereHas('assunto', fn ($q) => $q->servivel())->with('assunto')->get()
            ->groupBy(fn (DesempenhoAssunto $d) => $d->assunto->materia_id);
        $revisaoPorMateria = $fila->vencidasPorMateria($aluno);

        $materias = Materia::query()->withCount(['assuntos' => fn ($q) => $q->servivel()])->orderBy('ordem')->get()
            ->map(fn (Materia $materia) => [
                'slug' => $materia->slug,
                'nome' => $materia->nome,
                'area' => $materia->area,
                'nota' => $nota->notaDaMateria($desempenhos->get($materia->id, collect())),
                'assuntos_total' => $materia->assuntos_count,
                'revisao_hoje' => $revisaoPorMateria[$materia->id] ?? 0,
            ])->all();

        $chave = $request->session()->pull('vazio_revisao') ? 'vazio:revisao' : 'inicio';

        return Inertia::render('Inicio', [
            'materias' => $materias,
            'revisao_hoje_total' => min(array_sum($revisaoPorMateria), $fila->vagasHoje($aluno)),
            'revisao_amanha' => $fila->esperandoAmanha($aluno),
            'fala' => $falas->sortear($aluno, [$chave], Fala::HUMOR_NEUTRO),
        ]);
    }
}
