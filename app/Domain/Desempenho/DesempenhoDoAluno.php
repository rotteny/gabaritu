<?php

namespace App\Domain\Desempenho;

use App\Domain\Revisao\FilaDeRevisao;
use App\Models\User;

/** Payload de desempenho compartilhado por /desempenho (aluno) e /responsavel/alunos/{aluno} (somente leitura). */
class DesempenhoDoAluno
{
    public function __construct(private readonly PainelDeDesempenho $painel, private readonly FilaDeRevisao $fila) {}

    /** @return array{materias: array<int, array<string, mixed>>, revisao: array{hoje: int, amanha: int}} */
    public function montar(User $aluno): array
    {
        return [
            'materias' => $this->painel->montar($aluno),
            'revisao' => [
                'hoje' => min($this->fila->vencidas($aluno)->count(), $this->fila->vagasHoje($aluno)),
                'amanha' => $this->fila->esperandoAmanha($aluno),
            ],
        ];
    }
}
