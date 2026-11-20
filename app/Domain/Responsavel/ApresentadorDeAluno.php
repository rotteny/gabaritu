<?php

namespace App\Domain\Responsavel;

use App\Domain\Desempenho\ServicoDeNota;
use App\Models\ConviteResponsavel;
use App\Models\DesempenhoAssunto;
use App\Models\Materia;
use App\Models\User;
use App\Models\VinculoResponsavel;
use App\Support\EmailMascarado;
use Illuminate\Support\Collection;

class ApresentadorDeAluno
{
    public function __construct(private readonly ServicoDeNota $nota) {}

    /** @return array<string, mixed> visão do responsável sobre o aluno (docs/responsaveis.md) */
    public function paraResponsavel(User $aluno, User $responsavel): array
    {
        return [
            'id' => $aluno->id,
            'apelido' => $aluno->apelido,
            'serie' => $aluno->serie,
            'idade' => $aluno->idade(),
            'status' => $aluno->status,
            'teto_zoeira_responsavel' => $aluno->zoeira_responsavel ?? 'sem',
            'notas' => $this->notasPorMateria($aluno),
            'responsaveis' => $this->responsaveis($aluno)
                ->map(fn (VinculoResponsavel $v) => [...$this->dadosDoVinculo($v), 'sou_eu' => $v->responsavel_id === $responsavel->id])->all(),
            'convites_pendentes' => $this->convitesPendentes($aluno)
                ->map(fn (ConviteResponsavel $c) => [
                    'email_mascarado' => EmailMascarado::de($c->email),
                    'tipo' => $c->tipo,
                    'expira_em' => $c->expira_em->toIso8601String(),
                ])->all(),
        ];
    }

    /** @return array<int, array{slug: string, nome: string, nota: int|null}> */
    private function notasPorMateria(User $aluno): array
    {
        $desempenhos = DesempenhoAssunto::query()->where('user_id', $aluno->id)->with('assunto')->get()
            ->groupBy(fn (DesempenhoAssunto $d) => $d->assunto->materia_id);

        return Materia::query()->whereIn('id', $desempenhos->keys())->orderBy('ordem')->get()
            ->map(fn (Materia $materia) => [
                'slug' => $materia->slug,
                'nome' => $materia->nome,
                'nota' => $this->nota->notaDaMateria($desempenhos->get($materia->id, collect())),
            ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function responsaveisParaAluno(User $aluno): array
    {
        return $this->responsaveis($aluno)->map(fn (VinculoResponsavel $v) => $this->dadosDoVinculo($v))->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function convitesParaAluno(User $aluno): array
    {
        return $this->convitesPendentes($aluno)->map(fn (ConviteResponsavel $c) => [
            'id' => $c->id,
            'email_mascarado' => EmailMascarado::de($c->email),
            'parentesco' => $c->parentesco,
            'parentesco_outro' => $c->parentesco_outro,
            'expira_em' => $c->expira_em->toIso8601String(),
        ])->values()->all();
    }

    /** @return Collection<int, VinculoResponsavel> */
    private function responsaveis(User $aluno): Collection
    {
        return $aluno->vinculosComResponsaveis()->with('responsavel')->orderBy('id')->get();
    }

    /** @return Collection<int, ConviteResponsavel> */
    private function convitesPendentes(User $aluno): Collection
    {
        return ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)->orderBy('id')->get();
    }

    /** @return array<string, mixed> */
    private function dadosDoVinculo(VinculoResponsavel $vinculo): array
    {
        return [
            'nome' => $vinculo->responsavel->apelido,
            'parentesco' => $vinculo->parentesco,
            'parentesco_outro' => $vinculo->parentesco_outro,
            'email_mascarado' => EmailMascarado::de($vinculo->responsavel->email),
        ];
    }
}
