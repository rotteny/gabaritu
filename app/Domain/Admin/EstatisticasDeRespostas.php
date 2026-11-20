<?php

namespace App\Domain\Admin;

use App\Models\Tentativa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Página "Respostas": série diária, matérias e questionários mais feitos, abandono e etiquetas de erro. */
class EstatisticasDeRespostas
{
    private const int TOPO = 10;

    private const int CASAS_DA_PORCENTAGEM = 1;

    /** @return list<array{dia: string, respostas: int}> */
    public function serieDiaria(Periodo $periodo): array
    {
        $inicio = $periodo->inicio($this->primeiroDia());
        $porDia = DB::table('metricas_diarias')->where('dia', '>=', $inicio->format('Y-m-d'))->pluck('respostas', 'dia');

        $serie = [];
        for ($dia = $inicio; $dia <= Fuso::hoje(); $dia = $dia->addDay()) {
            $serie[] = ['dia' => $dia->format('Y-m-d'), 'respostas' => (int) ($porDia[$dia->format('Y-m-d')] ?? 0)];
        }

        return $serie;
    }

    /** @return array<int, array<string, mixed>> */
    public function materiasMaisFeitas(Periodo $periodo): array
    {
        return DB::table('respostas as r')
            ->join('questoes as q', 'q.id', '=', 'r.questao_id')->join('assuntos as a', 'a.id', '=', 'q.assunto_id')->join('materias as m', 'm.id', '=', 'a.materia_id')
            ->where('r.respondida_em', '>=', $this->inicioEmUtc($periodo))
            ->selectRaw('m.id, m.nome, count(*) as respostas, count(*) filter (where r.correta) as acertos')
            ->groupBy('m.id', 'm.nome')->orderByDesc('respostas')->limit(self::TOPO)->get()
            ->map(fn ($m) => ['id' => (int) $m->id, 'nome' => $m->nome, 'respostas' => (int) $m->respostas, 'pct_acerto' => $this->porcentagem($m->acertos, $m->respostas)])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function questionariosMaisFeitos(Periodo $periodo): array
    {
        return DB::table('tentativas as t')
            ->join('questionarios as qn', 'qn.id', '=', 't.questionario_id')->join('assuntos as a', 'a.id', '=', 'qn.assunto_id')
            ->where('t.tipo', Tentativa::TIPO_QUESTIONARIO)->where('t.iniciada_em', '>=', $this->inicioEmUtc($periodo))
            ->selectRaw('qn.id, qn.titulo, a.nome as assunto, count(*) as tentativas, count(t.finalizada_em) as concluidas')
            ->groupBy('qn.id', 'qn.titulo', 'a.nome')->orderByDesc('tentativas')->limit(self::TOPO)->get()
            ->map(fn ($q) => ['id' => (int) $q->id, 'titulo' => $q->titulo, 'assunto' => $q->assunto, 'tentativas' => (int) $q->tentativas, 'concluidas' => (int) $q->concluidas])->all();
    }

    /**
     * Abandonada: questionário iniciado há mais de `abandono_horas` e nunca finalizado (as mais recentes ainda podem estar em andamento).
     *
     * @return array{iniciadas: int, concluidas: int, abandonadas: int, taxa_pct: float|null}
     */
    public function abandono(Periodo $periodo): array
    {
        $limite = now()->subHours((int) config('gabaritu.admin.abandono_horas'));
        $linha = DB::table('tentativas')->where('tipo', Tentativa::TIPO_QUESTIONARIO)->where('iniciada_em', '>=', $this->inicioEmUtc($periodo))
            ->selectRaw(
                'count(*) filter (where finalizada_em is not null or iniciada_em < ?) as consideradas, '
                .'count(*) filter (where finalizada_em is not null) as concluidas, '
                .'count(*) filter (where finalizada_em is null and iniciada_em < ?) as abandonadas, count(*) as iniciadas',
                [$limite, $limite],
            )->first();

        return [
            'iniciadas' => (int) $linha->iniciadas,
            'concluidas' => (int) $linha->concluidas,
            'abandonadas' => (int) $linha->abandonadas,
            'taxa_pct' => $this->porcentagem($linha->abandonadas, $linha->consideradas),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function etiquetasDeErro(Periodo $periodo): array
    {
        return DB::table('respostas as r')
            ->join('alternativas as a', 'a.id', '=', 'r.alternativa_id')->join('etiquetas as e', 'e.id', '=', 'a.etiqueta_id')->join('materias as m', 'm.id', '=', 'e.materia_id')
            ->where('r.correta', false)->where('r.respondida_em', '>=', $this->inicioEmUtc($periodo))
            ->selectRaw('e.id, e.nome, m.nome as materia, count(*) as total')
            ->groupBy('e.id', 'e.nome', 'm.nome')->orderByDesc('total')->limit(self::TOPO)->get()
            ->map(fn ($e) => ['id' => (int) $e->id, 'nome' => $e->nome, 'materia' => $e->materia, 'total' => (int) $e->total])->all();
    }

    private function inicioEmUtc(Periodo $periodo): CarbonImmutable
    {
        return $periodo->inicio($this->primeiroDia())->setTimezone(config('app.timezone'));
    }

    private function primeiroDia(): ?CarbonImmutable
    {
        $primeiro = DB::table('metricas_diarias')->min('dia');

        return $primeiro === null ? null : CarbonImmutable::parse($primeiro, config('gabaritu.fuso'));
    }

    private function porcentagem(int|string|null $parte, int|string|null $total): ?float
    {
        return (int) $total === 0 ? null : round((int) $parte / (int) $total * 100, self::CASAS_DA_PORCENTAGEM);
    }
}
