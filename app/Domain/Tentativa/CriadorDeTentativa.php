<?php

namespace App\Domain\Tentativa;

use App\Domain\Revisao\FilaDeRevisao;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\Tentativa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CriadorDeTentativa
{
    private const int SEED_MAX = 2_000_000_000;

    public function __construct(private readonly FilaDeRevisao $fila) {}

    public function doQuestionario(User $aluno, Questionario $questionario): Tentativa
    {
        $aberta = Tentativa::query()
            ->where('user_id', $aluno->id)
            ->where('questionario_id', $questionario->id)
            ->whereNull('finalizada_em')
            ->latest('id')
            ->first();

        if ($aberta) {
            return $aberta;
        }

        $ids = Questao::query()
            ->where('questionario_id', $questionario->id)
            ->where('status', Questao::STATUS_ATIVA)
            ->orderBy('id')
            ->pluck('id');

        return $this->criar($aluno, Tentativa::TIPO_QUESTIONARIO, $ids, $questionario);
    }

    public function revisaoDoDia(User $aluno): ?Tentativa
    {
        $aberta = Tentativa::query()
            ->where('user_id', $aluno->id)
            ->where('tipo', Tentativa::TIPO_REVISAO)
            ->whereNull('finalizada_em')
            ->where('iniciada_em', '>=', $this->fila->hoje()->utc())
            ->latest('id')
            ->first();

        if ($aberta) {
            return $aberta;
        }

        $ids = $this->fila->questoesDoDia($aluno);

        return $ids->isEmpty() ? null : $this->criar($aluno, Tentativa::TIPO_REVISAO, $ids);
    }

    public function dasErradas(User $aluno, Tentativa $origem): ?Tentativa
    {
        $ids = $origem->respostas()
            ->where('correta', false)
            ->whereHas('questao', fn ($q) => $q->where('status', Questao::STATUS_ATIVA))
            ->orderBy('id')
            ->pluck('questao_id');

        return $ids->isEmpty() ? null : $this->criar($aluno, Tentativa::TIPO_REVISAO_ERRADAS, $ids);
    }

    /** @param  Collection<int, int>  $questaoIds */
    private function criar(User $aluno, string $tipo, Collection $questaoIds, ?Questionario $questionario = null): Tentativa
    {
        return DB::transaction(function () use ($aluno, $tipo, $questaoIds, $questionario): Tentativa {
            $tentativa = Tentativa::create([
                'user_id' => $aluno->id,
                'questionario_id' => $questionario?->id,
                'assunto_id' => $questionario?->assunto_id,
                'tipo' => $tipo,
                'seed' => random_int(1, self::SEED_MAX),
                'iniciada_em' => now(),
            ]);

            $tentativa->itens()->createMany(
                $questaoIds->values()->map(fn (int $id, int $posicao) => ['questao_id' => $id, 'posicao' => $posicao + 1])->all(),
            );

            return $tentativa;
        });
    }
}
