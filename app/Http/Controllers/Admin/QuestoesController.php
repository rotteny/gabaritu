<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\EstatisticasDeQuestoes;
use App\Domain\Admin\Paginacao;
use App\Http\Controllers\Controller;
use App\Models\Assunto;
use App\Models\Materia;
use App\Models\Questao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestoesController extends Controller
{
    public function __construct(private readonly EstatisticasDeQuestoes $questoes) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['origem', 'materia', 'assunto', 'status_juridico', 'suspeita']);

        return Inertia::render('admin/Questoes', [
            'filtros' => $filtros + ['origem' => null, 'materia' => null, 'assunto' => null, 'status_juridico' => null, 'suspeita' => null],
            'opcoes' => [
                'materias' => Materia::query()->orderBy('ordem')->get(['id', 'nome']),
                'assuntos' => Assunto::query()->orderBy('ordem')->get(['id', 'materia_id', 'nome']),
                'status_juridico' => EstatisticasDeQuestoes::STATUS_JURIDICO,
            ],
            'resumo' => $this->questoes->resumoPorMateriaEAssunto(),
            'questoes' => Paginacao::paraProps($this->questoes->listar($filtros), fn ($linha) => $linha),
        ]);
    }

    public function show(int $questao): Response
    {
        $registro = Questao::query()->findOrFail($questao);

        return Inertia::render('admin/Questao', [
            'questao' => $this->questoes->ficha($registro),
            'opcoes' => ['status_juridico' => EstatisticasDeQuestoes::STATUS_JURIDICO],
            'acoes' => [
                'status_juridico' => $registro->ehInep() ? route('admin.questoes.status-juridico', $registro, absolute: false) : null,
                'voltar' => route('admin.questoes', absolute: false),
            ],
        ]);
    }
}
