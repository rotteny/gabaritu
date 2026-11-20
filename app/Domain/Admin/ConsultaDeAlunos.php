<?php

namespace App\Domain\Admin;

use App\Enums\Serie;
use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Models\ConviteResponsavel;
use App\Models\Resposta;
use App\Models\User;
use App\Models\VinculoResponsavel;
use App\Support\EmailMascarado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Lista e ficha de aluno para o admin: e-mail sempre mascarado, agregados calculados no banco. */
class ConsultaDeAlunos
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, User>
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        return $this->comResumo()
            ->when(filled($filtros['busca'] ?? null), fn (Builder $q) => $this->buscar($q, (string) ($filtros['busca'] ?? '')))
            ->when(StatusConta::tryFrom((string) ($filtros['status'] ?? '')), fn (Builder $q, StatusConta $s) => $q->where('users.status', $s->value))
            ->when(Serie::tryFrom((string) ($filtros['serie'] ?? '')), fn (Builder $q, Serie $s) => $q->where('users.serie', $s->value))
            ->when(in_array($filtros['tem_responsavel'] ?? null, ['sim', 'nao'], true), fn (Builder $q) => $this->filtrarPorResponsavel($q, ($filtros['tem_responsavel'] ?? null) === 'sim'))
            ->orderByDesc('users.created_at')->orderByDesc('users.id')
            ->paginate((int) config('gabaritu.admin.por_pagina'))->withQueryString();
    }

    public function encontrar(int $id): User
    {
        return $this->comResumo()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    public function linha(User $aluno): array
    {
        return [
            'id' => $aluno->id,
            'apelido' => $aluno->apelido,
            'email_mascarado' => EmailMascarado::de($aluno->email),
            'serie' => $aluno->serie,
            'serie_rotulo' => Serie::tryFrom((string) $aluno->serie)?->rotulo(),
            'idade' => $aluno->ano_nascimento === null ? null : $aluno->idade(),
            'status' => $aluno->status,
            'cadastro' => Fuso::data($aluno->created_at),
            'ultima_atividade' => $this->ultimaAtividade($aluno),
            'respostas' => (int) $aluno->getAttribute('respostas_total'),
            'tem_responsavel' => (bool) $aluno->getAttribute('tem_responsavel'),
            'url' => route('admin.alunos.show', $aluno, absolute: false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function responsaveisVinculados(User $aluno): array
    {
        return VinculoResponsavel::query()->with('responsavel')->where('aluno_id', $aluno->id)->orderBy('aceito_em')->get()
            ->map(fn (VinculoResponsavel $v) => [
                'id' => $v->responsavel_id,
                'email_mascarado' => EmailMascarado::de((string) $v->responsavel?->email),
                'parentesco' => $v->parentesco,
                'parentesco_outro' => $v->parentesco_outro,
                'status' => $v->status,
                'aceito_em' => Fuso::data($v->aceito_em),
                'url' => route('admin.responsaveis.show', $v->responsavel_id, absolute: false),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function pedidosEConvitesPendentes(User $aluno): array
    {
        return ConviteResponsavel::query()->pendentes()->where('aluno_id', $aluno->id)->orderBy('created_at')->get()
            ->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'email_mascarado' => EmailMascarado::de($c->email),
                'parentesco' => $c->parentesco,
                'expira_em' => Fuso::data($c->expira_em),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function desempenhoPorMateria(User $aluno): array
    {
        $notas = DB::table('desempenhos_assunto as d')
            ->join('assuntos as a', 'a.id', '=', 'd.assunto_id')
            ->where('d.user_id', $aluno->id)->whereNotNull('d.nota')
            ->selectRaw('a.materia_id, round(avg(d.nota)) as nota_media, count(*) as assuntos_com_nota')
            ->groupBy('a.materia_id')->get()->keyBy('materia_id');

        $respostas = DB::table('respostas as r')
            ->join('questoes as q', 'q.id', '=', 'r.questao_id')->join('assuntos as a', 'a.id', '=', 'q.assunto_id')
            ->where('r.user_id', $aluno->id)
            ->selectRaw('a.materia_id, count(*) as total, count(*) filter (where r.correta) as acertos')
            ->groupBy('a.materia_id')->get()->keyBy('materia_id');

        return DB::table('materias')->whereIn('id', $notas->keys()->merge($respostas->keys())->unique())->orderBy('ordem')->get()
            ->map(fn ($m) => [
                'materia' => $m->nome,
                'nota_media' => isset($notas[$m->id]) ? (int) $notas[$m->id]->nota_media : null,
                'assuntos_com_nota' => (int) ($notas[$m->id]->assuntos_com_nota ?? 0),
                'respostas' => (int) ($respostas[$m->id]->total ?? 0),
                'pct_acerto' => isset($respostas[$m->id]) ? round($respostas[$m->id]->acertos / $respostas[$m->id]->total * 100, 1) : null,
            ])->all();
    }

    /** @return Builder<User> */
    private function comResumo(): Builder
    {
        return User::query()->where('users.tipo', TipoConta::Aluno->value)
            ->select('users.*')
            ->selectSub(Resposta::query()->selectRaw('count(*)')->whereColumn('respostas.user_id', 'users.id'), 'respostas_total')
            ->selectSub(Resposta::query()->selectRaw('max(respondida_em)')->whereColumn('respostas.user_id', 'users.id'), 'ultima_resposta_em')
            ->selectSub($this->vinculoAtivo()->selectRaw('count(*) > 0'), 'tem_responsavel');
    }

    /** @return \Illuminate\Database\Query\Builder */
    private function vinculoAtivo()
    {
        return DB::table('vinculos_responsavel')->whereColumn('vinculos_responsavel.aluno_id', 'users.id')->where('status', VinculoResponsavel::ATIVO);
    }

    /** @param  Builder<User>  $consulta */
    private function buscar(Builder $consulta, string $busca): void
    {
        $termo = BuscaDeTexto::escapar($busca);
        $consulta->where(fn (Builder $q) => $q
            ->whereRaw('lower(users.apelido) like ?', ["%{$termo}%"])
            ->orWhereRaw('lower(users.email) = ?', [mb_strtolower(trim($busca))]));
    }

    /** @param  Builder<User>  $consulta */
    private function filtrarPorResponsavel(Builder $consulta, bool $tem): void
    {
        $tem ? $consulta->whereExists($this->vinculoAtivo()) : $consulta->whereNotExists($this->vinculoAtivo());
    }

    private function ultimaAtividade(User $aluno): ?string
    {
        $momentos = array_map(
            fn (string $coluna) => $aluno->getAttribute($coluna) === null ? null : Carbon::parse($aluno->getAttribute($coluna), config('app.timezone')),
            ['ultimo_acesso_em', 'ultima_resposta_em'],
        );
        $momentos = array_filter($momentos);

        return $momentos === [] ? null : Fuso::data(max($momentos));
    }
}
