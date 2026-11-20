<?php

namespace App\Domain\Tentativa;

use App\Enums\MotivoDoReport;
use App\Enums\StatusDoReport;
use App\Models\Questao;
use App\Models\ReportQuestao;
use App\Models\Resposta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** "Reportar erro" do aluno: só de questão que ele já respondeu, 1 por questão e no máximo 20 por dia. */
class ReportadorDeQuestao
{
    public const int LIMITE_POR_DIA = 20;

    public const int MAXIMO_DO_COMENTARIO = 300;

    public function reportar(User $aluno, Questao $questao, MotivoDoReport $motivo, ?string $comentario): ResultadoDoReport
    {
        if (! Resposta::query()->where('user_id', $aluno->id)->where('questao_id', $questao->id)->exists()) {
            return ResultadoDoReport::NaoRespondeu;
        }

        if ($this->reportsDeHoje($aluno) >= self::LIMITE_POR_DIA) {
            return ResultadoDoReport::LimiteDiario;
        }

        $gravou = ReportQuestao::query()->insertOrIgnore([
            'questao_id' => $questao->id,
            'user_id' => $aluno->id,
            'motivo' => $motivo->value,
            'comentario' => $comentario === null || trim($comentario) === '' ? null : trim($comentario),
            'status' => StatusDoReport::Aberto->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $gravou === 1 ? ResultadoDoReport::Registrado : ResultadoDoReport::JaReportou;
    }

    private function reportsDeHoje(User $aluno): int
    {
        $inicio = now(config('gabaritu.fuso'))->startOfDay()->setTimezone(config('app.timezone'));

        return DB::table('reports_questao')->where('user_id', $aluno->id)->where('created_at', '>=', $inicio)->count();
    }
}
