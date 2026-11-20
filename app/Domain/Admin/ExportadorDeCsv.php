<?php

namespace App\Domain\Admin;

use Illuminate\Support\Facades\DB;

/** CSV só com números agregados: nunca e-mail, apelido nem id de aluno. Texto que começaria com =, +, -, @, tab ou retorno de carro ganha aspa simples (injeção em planilha). */
class ExportadorDeCsv
{
    /** @var list<string> */
    public const array TIPOS = ['metricas', 'cadastros', 'questoes', 'retencao'];

    public function __construct(private readonly RetencaoPorCoorte $retencao, private readonly EstatisticasDeQuestoes $questoes) {}

    /** @return array{cabecalho: list<string>, linhas: iterable<list<scalar|null>>} */
    public function montar(string $tipo): array
    {
        return match ($tipo) {
            'metricas' => $this->metricas(),
            'cadastros' => $this->cadastros(),
            'questoes' => $this->questoesComAcerto(),
            'retencao' => $this->retencaoPorSemana(),
            default => throw new \InvalidArgumentException('Tipo de exportação desconhecido.'),
        };
    }

    public static function proteger(mixed $valor): mixed
    {
        return is_string($valor) && $valor !== '' && str_contains("=+-@\t\r", $valor[0]) ? "'".$valor : $valor;
    }

    /** @return array{cabecalho: list<string>, linhas: iterable<list<scalar|null>>} */
    private function metricas(): array
    {
        $linhas = DB::table('metricas_diarias')->orderBy('dia')->cursor()->map(fn (object $l) => [$l->dia, $l->alunos_ativos, $l->logins, $l->respostas, $l->visitas_landing]);

        return ['cabecalho' => ['dia', 'alunos_ativos', 'logins', 'respostas', 'visitas_landing'], 'linhas' => $linhas];
    }

    /** @return array{cabecalho: list<string>, linhas: iterable<list<scalar|null>>} */
    private function cadastros(): array
    {
        $linhas = DB::table('users')->where('tipo', 'aluno')
            ->selectRaw(Fuso::diaSql('created_at').' as dia, count(*) as total') // @phpstan-ignore argument.type
            ->groupBy('dia')->orderBy('dia')->cursor()->map(fn (object $l) => [$l->dia, $l->total]);

        return ['cabecalho' => ['dia', 'cadastros'], 'linhas' => $linhas];
    }

    /** @return array{cabecalho: list<string>, linhas: iterable<list<scalar|null>>} */
    private function questoesComAcerto(): array
    {
        $linhas = $this->questoes->todasParaExportar()->map(fn (array $q) => [
            $q['codigo'], $q['origem'], $q['materia'], $q['assunto'], $q['status_juridico'], $q['respostas'], $q['pct_acerto'], $q['tempo_medio_ms'], $q['reports_abertos'],
        ]);

        return ['cabecalho' => ['codigo', 'origem', 'materia', 'assunto', 'status_juridico', 'respostas', 'pct_acerto', 'tempo_medio_ms', 'reports_abertos'], 'linhas' => $linhas];
    }

    /** @return array{cabecalho: list<string>, linhas: iterable<list<scalar|null>>} */
    private function retencaoPorSemana(): array
    {
        $marcos = array_map('intval', config('gabaritu.admin.retencao.marcos'));
        $cabecalho = ['semana', 'cadastros'];
        foreach ($marcos as $n) {
            array_push($cabecalho, "d{$n}_base", "d{$n}_voltaram", "d{$n}_pct");
        }

        $linhas = collect($this->retencao->coortes())->map(function (array $c) use ($marcos): array {
            $linha = [$c['semana'], $c['cadastros']];
            foreach ($marcos as $n) {
                array_push($linha, $c["d{$n}"]['base'], $c["d{$n}"]['voltaram'], $c["d{$n}"]['pct']);
            }

            return $linha;
        });

        return ['cabecalho' => $cabecalho, 'linhas' => $linhas];
    }
}
