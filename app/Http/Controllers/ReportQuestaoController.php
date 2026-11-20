<?php

namespace App\Http\Controllers;

use App\Domain\Tentativa\ReportadorDeQuestao;
use App\Domain\Tentativa\ResultadoDoReport;
use App\Enums\MotivoDoReport;
use App\Models\Questao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** Endpoint JSON do botão "Reportar erro" da tela da questão (abre em dialog, sem recarregar a página). */
class ReportQuestaoController extends Controller
{
    public function store(Request $request, Questao $questao, ReportadorDeQuestao $reportador): JsonResponse
    {
        $dados = $request->validate([
            'motivo' => ['required', Rule::enum(MotivoDoReport::class)],
            'comentario' => ['nullable', 'string', 'max:'.ReportadorDeQuestao::MAXIMO_DO_COMENTARIO],
        ]);

        $resultado = $reportador->reportar($request->user(), $questao, MotivoDoReport::from($dados['motivo']), $dados['comentario'] ?? null);

        return match ($resultado) {
            ResultadoDoReport::Registrado => response()->json(['ok' => true], Response::HTTP_CREATED),
            ResultadoDoReport::JaReportou => response()->json(['message' => 'Você já reportou esta questão. Obrigado!'], Response::HTTP_UNPROCESSABLE_ENTITY),
            ResultadoDoReport::LimiteDiario => response()->json(['message' => 'Você já enviou muitos reports hoje. Tente amanhã.'], Response::HTTP_TOO_MANY_REQUESTS),
            ResultadoDoReport::NaoRespondeu => response()->json(['message' => 'Responda a questão antes de reportar.'], Response::HTTP_FORBIDDEN),
        };
    }
}
