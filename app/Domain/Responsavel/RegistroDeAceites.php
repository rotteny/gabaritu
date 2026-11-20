<?php

namespace App\Domain\Responsavel;

use App\Models\AceiteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\VinculoResponsavel;

/** Guarda a prova do aceite e da aprovação numa tabela própria, que sobrevive ao convite apagado. */
class RegistroDeAceites
{
    public function registrar(ConviteResponsavel $convite, VinculoResponsavel $vinculo): AceiteDeResponsavel
    {
        return AceiteDeResponsavel::query()->create([
            'convite_id' => $convite->id,
            'tipo' => $convite->tipo,
            'aluno_id' => $vinculo->aluno_id,
            'responsavel_id' => $vinculo->responsavel_id,
            'aprovado_por_id' => $convite->aprovado_por_id,
            'aceito_em' => $vinculo->aceito_em,
            'aceite_ip' => $vinculo->aceite_ip,
            'termos_versao' => $vinculo->termos_versao,
            'privacidade_versao' => $vinculo->privacidade_versao,
            'aluno_aceitou_em' => $convite->aluno_aceitou_em,
            'aprovado_em' => $convite->aprovado_em,
        ]);
    }
}
