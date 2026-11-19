<?php

namespace App\Domain\Diagnostico;

final class Diagnostico
{
    private const int PERCENTUAL_TOTAL = 100;

    /**
     * @param  list<array{etiqueta_id: int|null, ordem: int}>  $erros  Erros válidos da janela; maior `ordem` = mais recente.
     * @return list<ItemDiagnostico>
     */
    public function calcular(array $erros): array
    {
        $total = count($erros);

        if ($total < (int) config('gabaritu.diagnostico.min_erros')) {
            return [];
        }

        $grupos = $this->agrupar($erros);
        uasort($grupos, fn (array $a, array $b) => [$b['n'], $b['recente'], $a['id']] <=> [$a['n'], $a['recente'], $b['id']]);

        $itens = [];
        foreach ($grupos as $grupo) {
            if ($grupo['n'] >= (int) config('gabaritu.diagnostico.min_ocorrencias')) {
                $itens[] = new ItemDiagnostico($grupo['id'], $grupo['n'], (int) round(self::PERCENTUAL_TOTAL * $grupo['n'] / $total));
            }
        }

        return array_slice($itens, 0, (int) config('gabaritu.diagnostico.max_exibidas'));
    }

    /**
     * @param  list<array{etiqueta_id: int|null, ordem: int}>  $erros
     * @return array<int, array{id: int, n: int, recente: int}>
     */
    private function agrupar(array $erros): array
    {
        $grupos = [];
        foreach ($erros as $erro) {
            $id = $erro['etiqueta_id'];
            if ($id === null) {
                continue;
            }
            $grupos[$id] ??= ['id' => $id, 'n' => 0, 'recente' => 0];
            $grupos[$id]['n']++;
            $grupos[$id]['recente'] = max($grupos[$id]['recente'], $erro['ordem']);
        }

        return $grupos;
    }
}
