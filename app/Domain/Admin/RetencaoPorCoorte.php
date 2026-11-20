<?php

namespace App\Domain\Admin;

use Illuminate\Support\Facades\DB;

/**
 * Coortes por semana de cadastro (semana começa na segunda, no fuso do aluno). "Voltou em Dn" é ter respondido
 * algo em algum dia entre o dia seguinte ao cadastro e o dia n depois dele. Só as datas das respostas entram: nada novo é coletado.
 * Um aluno só entra na base de Dn depois que o dia n já passou, para a coorte recente não parecer pior do que é.
 */
class RetencaoPorCoorte
{
    private const int CASAS_DA_PORCENTAGEM = 1;

    /** @return array<int, array<string, mixed>> */
    public function coortes(): array
    {
        $marcos = array_values(array_map('intval', config('gabaritu.admin.retencao.marcos')));
        $semanas = (int) config('gabaritu.admin.retencao.semanas');
        $hoje = Fuso::hoje()->format('Y-m-d');
        $inicio = Fuso::hoje()->startOfWeek()->subWeeks($semanas - 1)->setTimezone(config('app.timezone'));
        $diaCadastro = Fuso::diaSql('u.created_at');
        $diaResposta = Fuso::diaSql('r.respondida_em');

        $colunas = [];
        foreach ($marcos as $n) {
            $colunas[] = "count(*) filter (where c.d0 + {$n} <= ?::date) as base_{$n}";
            $colunas[] = "count(*) filter (where c.d0 + {$n} <= ?::date and exists (select 1 from respostas r where r.user_id = c.id and {$diaResposta} between c.d0 + 1 and c.d0 + {$n})) as voltou_{$n}";
        }

        $sql = 'select c.semana, count(*) as total, '.implode(', ', $colunas)
            ." from (select u.id, {$diaCadastro} as d0, date_trunc('week', {$diaCadastro})::date as semana from users u where u.tipo = 'aluno' and u.created_at >= ?) c"
            .' group by c.semana order by c.semana desc';

        $bindings = [];
        foreach ($marcos as $n) {
            array_push($bindings, $hoje, $hoje);
        }
        $bindings[] = $inicio;

        return array_map(fn (object $linha) => $this->linha($linha, $marcos), DB::select($sql, $bindings));
    }

    /**
     * @param  list<int>  $marcos
     * @return array<string, mixed>
     */
    private function linha(object $linha, array $marcos): array
    {
        $dados = (array) $linha;
        $retencao = [];
        foreach ($marcos as $n) {
            $base = (int) $dados["base_{$n}"];
            $retencao["d{$n}"] = ['base' => $base, 'voltaram' => (int) $dados["voltou_{$n}"], 'pct' => $base === 0 ? null : round($dados["voltou_{$n}"] / $base * 100, self::CASAS_DA_PORCENTAGEM)];
        }

        return ['semana' => $dados['semana'], 'cadastros' => (int) $dados['total']] + $retencao;
    }
}
