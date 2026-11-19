<?php

namespace App\Domain\Desempenho;

use App\Domain\Nota\NotaCalculator;
use App\Models\DesempenhoAssunto;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Questao;
use App\Models\Resposta;
use App\Models\User;
use Illuminate\Support\Collection;

class ServicoDeNota
{
    public function __construct(private readonly NotaCalculator $calculadora) {}

    public function registrar(User $aluno, Questao $questao, Resposta $resposta): void
    {
        $desempenho = $this->doAssunto($aluno, $questao->assunto_id, bloquear: true);

        $resultado = $this->calculadora->atualizar(
            $desempenho->theta,
            $questao->dificuldade,
            $resposta->correta,
            $desempenho->n_primeiras,
            $resposta->repetida,
        );

        $desempenho->update([
            'theta' => $resultado->theta,
            'n_primeiras' => $resultado->nPrimeiras,
            'nota' => $resultado->nota,
            'calculado_em' => now(),
        ]);

        DesempenhoAssuntoHistorico::create([
            'user_id' => $aluno->id,
            'assunto_id' => $questao->assunto_id,
            'resposta_id' => $resposta->id,
            'theta' => $resultado->theta,
            'nota' => $resultado->nota,
            'n_primeiras' => $resultado->nPrimeiras,
            'calculado_em' => now(),
        ]);
    }

    /** @return array{nota: int|null, amostra_baixa: bool, faltam: int} */
    public function resumo(?DesempenhoAssunto $desempenho): array
    {
        $n = $desempenho ? $desempenho->n_primeiras : 0;
        $nota = $desempenho ? $this->calculadora->exibida($desempenho->theta, $n) : null;

        return [
            'nota' => $nota,
            'amostra_baixa' => $nota !== null && $this->calculadora->amostraBaixa($n),
            'faltam' => max(0, (int) config('gabaritu.nota.min_respostas_nota') - $n),
        ];
    }

    /** @param  Collection<int, DesempenhoAssunto>  $desempenhos */
    public function notaDaMateria(Collection $desempenhos): ?int
    {
        $minimo = (int) config('gabaritu.nota.min_respostas_nota');
        $validos = $desempenhos->filter(fn (DesempenhoAssunto $d) => $d->n_primeiras >= $minimo);

        return $validos->isEmpty() ? null : $this->calculadora->exibida((float) $validos->avg('theta'), $minimo);
    }

    /** @param  Collection<int, DesempenhoAssunto>  $desempenhos */
    public function amostraBaixaDaMateria(Collection $desempenhos): bool
    {
        $minimo = (int) config('gabaritu.nota.min_respostas_nota');

        return $desempenhos->contains(fn (DesempenhoAssunto $d) => $d->n_primeiras >= $minimo && $this->calculadora->amostraBaixa($d->n_primeiras));
    }

    public function exibidaDeSnapshot(int $notaInterna, int $nPrimeiras): ?int
    {
        $theta = ($notaInterna - (float) config('gabaritu.nota.nota_base')) / (float) config('gabaritu.nota.nota_escala');

        return $this->calculadora->exibida($theta, $nPrimeiras);
    }

    private function doAssunto(User $aluno, int $assuntoId, bool $bloquear): DesempenhoAssunto
    {
        $consulta = DesempenhoAssunto::query()->where('user_id', $aluno->id)->where('assunto_id', $assuntoId);

        return ($bloquear ? $consulta->lockForUpdate() : $consulta)->first()
            ?? DesempenhoAssunto::create([
                'user_id' => $aluno->id,
                'assunto_id' => $assuntoId,
                'theta' => (float) config('gabaritu.nota.theta_inicial'),
                'n_primeiras' => 0,
            ]);
    }
}
