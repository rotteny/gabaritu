<?php

namespace App\Domain\Revisao;

use App\Models\Questao;
use App\Models\Resposta;
use App\Models\RevisaoEspacada as Revisao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FilaDeRevisao
{
    public const string CONTEXTO_DO_DIA = 'revisao';

    public function __construct(private readonly RevisaoEspacada $regras) {}

    public function hoje(): CarbonImmutable
    {
        return now(config('gabaritu.fuso'))->startOfDay();
    }

    public function aplicar(User $aluno, Questao $questao, bool $correta, bool $chute, bool $emRevisao): void
    {
        $registro = Revisao::query()->where('user_id', $aluno->id)->where('questao_id', $questao->id)->first();
        $atual = $registro
            ? new EstadoRevisao($registro->caixa, $registro->acertos_seguidos, CarbonImmutable::parse($registro->proxima_em))
            : null;

        $decisao = $this->regras->decidir($atual, $correta, $chute, $emRevisao, $this->hoje());

        match ($decisao->acao) {
            AcaoRevisao::Manter => null,
            AcaoRevisao::Remover => $registro?->delete(),
            AcaoRevisao::Gravar => Revisao::updateOrCreate(
                ['user_id' => $aluno->id, 'questao_id' => $questao->id],
                [
                    'caixa' => $decisao->estado?->caixa,
                    'acertos_seguidos' => $decisao->estado?->acertosSeguidos,
                    'proxima_em' => $decisao->estado?->proximaEm->toDateString(),
                ],
            ),
        };
    }

    /** @return Builder<Revisao> */
    public function vencidas(User $aluno): Builder
    {
        return Revisao::query()
            ->where('user_id', $aluno->id)
            ->where('proxima_em', '<=', $this->hoje()->toDateString())
            ->whereHas('questao', fn (Builder $q) => $q->where('status', Questao::STATUS_ATIVA));
    }

    public function vagasHoje(User $aluno): int
    {
        $feitas = Resposta::query()
            ->where('user_id', $aluno->id)
            ->where('contexto', self::CONTEXTO_DO_DIA)
            ->where('respondida_em', '>=', $this->hoje()->utc())
            ->count();

        return max(0, (int) config('gabaritu.revisao.max_dia') - $feitas);
    }

    /** @return Collection<int, int> ids das questões da revisão do dia, mais atrasadas primeiro */
    public function questoesDoDia(User $aluno): Collection
    {
        return $this->vencidas($aluno)
            ->orderBy('proxima_em')->orderBy('caixa')->orderBy('questao_id')
            ->limit($this->vagasHoje($aluno))
            ->pluck('questao_id');
    }

    /** @return array<int, int> materia_id => quantidade vencida */
    public function vencidasPorMateria(User $aluno): array
    {
        return $this->vencidas($aluno)
            ->join('questoes', 'questoes.id', '=', 'revisoes_espacadas.questao_id')
            ->join('assuntos', 'assuntos.id', '=', 'questoes.assunto_id')
            ->groupBy('assuntos.materia_id')
            ->selectRaw('assuntos.materia_id as materia_id, count(*) as total')
            ->pluck('total', 'materia_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function esperandoAmanha(User $aluno): int
    {
        return max(0, $this->vencidas($aluno)->count() - $this->vagasHoje($aluno));
    }
}
