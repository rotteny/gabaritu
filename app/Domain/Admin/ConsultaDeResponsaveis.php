<?php

namespace App\Domain\Admin;

use App\Enums\TipoConta;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use App\Support\EmailMascarado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/** Responsável para o admin: só e-mail mascarado e contagens; o nome real nem sai do banco para o painel. */
class ConsultaDeResponsaveis
{
    /** @return LengthAwarePaginator<int, User> */
    public function listar(?string $busca): LengthAwarePaginator
    {
        return $this->comResumo()
            ->when(filled($busca), fn (Builder $q) => $q->whereRaw('lower(users.email) like ?', [BuscaDeTexto::escapar((string) $busca).'%']))
            ->orderByDesc('users.created_at')->orderByDesc('users.id')
            ->paginate((int) config('gabaritu.admin.por_pagina'))->withQueryString();
    }

    public function encontrar(int $id): User
    {
        return $this->comResumo()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    public function linha(User $responsavel): array
    {
        return [
            'id' => $responsavel->id,
            'email_mascarado' => EmailMascarado::de($responsavel->email),
            'alunos_vinculados' => (int) $responsavel->getAttribute('alunos_total'),
            'convites_pendentes' => (int) $responsavel->getAttribute('convites_total'),
            'cadastro' => Fuso::data($responsavel->created_at),
            'url' => route('admin.responsaveis.show', $responsavel, absolute: false),
        ];
    }

    /** @return array<int, array<string, mixed>> só o apelido do aluno, como manda a spec */
    public function alunosVinculados(User $responsavel): array
    {
        return VinculoResponsavel::query()->with('aluno:id,apelido')->where('responsavel_id', $responsavel->id)->orderBy('aceito_em')->get()
            ->map(fn (VinculoResponsavel $v) => [
                'id' => $v->aluno_id,
                'apelido' => $v->aluno?->apelido,
                'parentesco' => $v->parentesco,
                'status' => $v->status,
                'url' => route('admin.alunos.show', $v->aluno_id, absolute: false),
            ])->all();
    }

    /** @return Builder<User> */
    private function comResumo(): Builder
    {
        return User::query()->where('users.tipo', TipoConta::Responsavel->value)
            ->select('users.*')
            ->selectSub(
                VinculoResponsavel::query()->selectRaw('count(*)')->whereColumn('vinculos_responsavel.responsavel_id', 'users.id')->where('status', VinculoResponsavel::ATIVO),
                'alunos_total',
            )
            ->selectSub(
                ConviteResponsavel::query()->pendentes()->selectRaw('count(*)')->whereRaw('lower(convites_responsavel.email) = lower(users.email)'),
                'convites_total',
            );
    }
}
