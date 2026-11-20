<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\EstatisticasDeQuestoes;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Questao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Troca o status jurídico de uma questão INEP. `remover` já some para o aluno: o escopo `servivel` lê o banco a cada pedido. */
class StatusJuridicoController extends Controller
{
    public function __construct(private readonly Auditoria $auditoria) {}

    public function __invoke(Request $request, int $questao): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);

        $dados = $request->validate(['status' => ['required', Rule::in(EstatisticasDeQuestoes::STATUS_JURIDICO)]]);

        DB::transaction(function () use ($admin, $questao, $dados): void {
            $registro = Questao::query()->where('origem', Questao::ORIGEM_INEP)->lockForUpdate()->findOrFail($questao);
            $de = $registro->revisao_juridica ?? EstatisticasDeQuestoes::JURIDICO_PENDENTE;

            if ($de === $dados['status']) {
                return;
            }

            $registro->update(['revisao_juridica' => $dados['status']]);
            $this->auditoria->registrar($admin, AcaoDeAuditoria::StatusJuridicoAlterado, $registro, detalhes: ['de' => $de, 'para' => $dados['status']]);
        });

        return redirect()->route('admin.questoes.show', $questao);
    }
}
