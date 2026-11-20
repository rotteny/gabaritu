<?php

namespace App\Domain\Responsavel;

use App\Domain\Desempenho\ServicoDeNota;
use App\Enums\TipoConvite;
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
            'avatar' => $aluno->avatar,
            'idade' => $aluno->idade(),
            'status' => $aluno->status,
            'teto_zoeira_responsavel' => $aluno->zoeira_responsavel ?? 'sem',
            'notas' => $this->notasPorMateria($aluno),
            'responsaveis' => $this->responsaveis($aluno)
                ->map(fn (VinculoResponsavel $v) => ['id' => $v->responsavel_id, ...$this->dadosDoVinculo($v), 'sou_eu' => $v->responsavel_id === $responsavel->id])->all(),
            'convites_pendentes' => $this->convites($aluno)->filter(fn (ConviteResponsavel $c) => ! TipoConvite::from($c->tipo)->ehPedidoDoResponsavel())
                ->map(fn (ConviteResponsavel $c) => [
                    'email_mascarado' => EmailMascarado::de($c->email),
                    'tipo' => $c->tipo,
                    'expira_em' => $c->expira_em->toIso8601String(),
                ])->values()->all(),
            'pedidos_pendentes' => $this->pedidosAguardandoResponsavel($aluno),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function responsaveisParaAluno(User $aluno): array
    {
        return $this->responsaveis($aluno)->map(fn (VinculoResponsavel $v) => ['id' => $v->responsavel_id, ...$this->dadosDoVinculo($v)])->values()->all();
    }

    /** @return array<int, array<string, mixed>> convites de responsável pendentes (os pedidos vêm em `pedidosParaAluno`) */
    public function convitesParaAluno(User $aluno): array
    {
        return $this->convites($aluno)->filter(fn (ConviteResponsavel $c) => ! TipoConvite::from($c->tipo)->ehPedidoDoResponsavel())
            ->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'email_mascarado' => EmailMascarado::de($c->email),
                'parentesco' => $c->parentesco,
                'parentesco_outro' => $c->parentesco_outro,
                'expira_em' => $c->expira_em->toIso8601String(),
            ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> pedidos de vínculo feitos por adultos ao aluno (seção "Quem acompanha você") */
    public function pedidosParaAluno(User $aluno): array
    {
        return $this->convites($aluno)->filter(fn (ConviteResponsavel $c) => $c->tipo === TipoConvite::PedidoVinculo->value)
            ->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'solicitante' => $this->solicitante($c),
                'expira_em' => $c->expira_em->toIso8601String(),
                'aguarda_responsavel' => $c->exige_aprovacao && $c->aprovado_em === null,
                'aceito_por_mim' => $c->aluno_aceitou_em !== null,
            ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> pedidos feitos pelo próprio responsável (painel) */
    public function pedidosEnviados(User $responsavel): array
    {
        return ConviteResponsavel::query()->pendentes()->pedidos()->where('solicitante_id', $responsavel->id)->orderBy('id')->get()
            ->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'email_mascarado' => EmailMascarado::de($c->email),
                'parentesco' => $c->parentesco,
                'parentesco_outro' => $c->parentesco_outro,
                'expira_em' => $c->expira_em->toIso8601String(),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function pedidosAguardandoResponsavel(User $aluno): array
    {
        return $this->convites($aluno)
            ->filter(fn (ConviteResponsavel $c) => $c->tipo === TipoConvite::PedidoVinculo->value && $c->exige_aprovacao && $c->aprovado_em === null)
            ->map(fn (ConviteResponsavel $c) => [
                'id' => $c->id,
                'solicitante' => $this->solicitante($c),
                'aluno_aceitou' => $c->aluno_aceitou_em !== null,
                'expira_em' => $c->expira_em->toIso8601String(),
            ])->values()->all();
    }

    /** @return array<string, mixed> */
    private function solicitante(ConviteResponsavel $convite): array
    {
        $solicitante = $convite->solicitante;

        return [
            'nome' => $solicitante?->apelido,
            'parentesco' => $convite->parentesco,
            'parentesco_outro' => $convite->parentesco_outro,
            'email_mascarado' => EmailMascarado::de((string) $solicitante?->email),
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

    /** @return Collection<int, VinculoResponsavel> */
    private function responsaveis(User $aluno): Collection
    {
        return $aluno->vinculosComResponsaveis()->with('responsavel')->orderBy('id')->get();
    }

    /** @return Collection<int, ConviteResponsavel> */
    private function convites(User $aluno): Collection
    {
        return ConviteResponsavel::query()->pendentes()->with('solicitante')->where('aluno_id', $aluno->id)->orderBy('id')->get();
    }

    /** @return array<string, mixed> */
    private function dadosDoVinculo(VinculoResponsavel $vinculo): array
    {
        return [
            'nome' => $vinculo->responsavel->apelido,
            'parentesco' => $vinculo->parentesco,
            'parentesco_outro' => $vinculo->parentesco_outro,
            'email_mascarado' => EmailMascarado::de($vinculo->responsavel->email),
            'status' => $vinculo->status,
            'autorizado_em' => $vinculo->aceito_em->toIso8601String(),
        ];
    }
}
