<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\ConsultaDeReports;
use App\Domain\Admin\Paginacao;
use App\Enums\MotivoDoReport;
use App\Enums\StatusDoReport;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Questao;
use App\Models\ReportQuestao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __construct(private readonly ConsultaDeReports $reports) {}

    public function index(Request $request): Response
    {
        $status = StatusDoReport::tryFrom((string) $request->query('status')) ?? StatusDoReport::Aberto;
        $pagina = $this->reports->fila($status);
        $detalhes = $this->reports->reportsDe($pagina->getCollection(), $status);

        return Inertia::render('admin/Reports', [
            'filtros' => ['status' => $status->value],
            'opcoes' => ['status' => array_column(StatusDoReport::cases(), 'value'), 'motivos' => MotivoDoReport::opcoes()],
            'contagens' => $this->reports->contagemPorStatus(),
            'fila' => Paginacao::paraProps($pagina, fn ($q) => [
                'questao' => [
                    'id' => (int) $q->id, 'codigo' => $q->codigo, 'origem' => $q->origem, 'materia' => $q->materia, 'assunto' => $q->assunto,
                    'url' => route('admin.questoes.show', $q->id, absolute: false),
                ],
                'total' => (int) $q->total,
                'por_motivo' => $this->reports->porMotivo((int) $q->id, $status),
                'reports' => $detalhes[$q->id] ?? [],
                'atualizar_todos' => route('admin.reports.questao', $q->id, absolute: false),
            ]),
        ]);
    }

    public function update(Request $request, int $report): RedirectResponse
    {
        $dados = $request->validate(['status' => ['required', Rule::enum(StatusDoReport::class)]]);
        $this->reports->atualizar($this->admin(), ReportQuestao::query()->findOrFail($report), StatusDoReport::from($dados['status']));

        return back();
    }

    public function updateQuestao(Request $request, int $questao): RedirectResponse
    {
        $dados = $request->validate(['status' => ['required', Rule::in([StatusDoReport::Resolvido->value, StatusDoReport::Descartado->value])]]);
        $this->reports->atualizarDaQuestao($this->admin(), Questao::query()->findOrFail($questao), StatusDoReport::from($dados['status']));

        return back();
    }

    private function admin(): Admin
    {
        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);

        return $admin;
    }
}
