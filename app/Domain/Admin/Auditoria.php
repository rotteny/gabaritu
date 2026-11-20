<?php

namespace App\Domain\Admin;

use App\Models\Admin;
use App\Models\AdminAuditoria;
use App\Models\Questao;
use App\Models\ReportQuestao;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Grava uma linha em admin_auditoria: quem, o quê, em quem, por quê e quando. Sem IP nem user-agent. */
class Auditoria
{
    /** @param  array<string, mixed>|null  $detalhes */
    public function registrar(Admin $admin, AcaoDeAuditoria $acao, ?Model $alvo = null, ?string $motivo = null, ?array $detalhes = null): AdminAuditoria
    {
        return AdminAuditoria::create([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'acao' => $acao->value,
            'alvo_tipo' => $alvo === null ? null : $this->tipoDe($alvo),
            'alvo_id' => $alvo?->getKey(),
            'motivo' => $motivo,
            'detalhes' => $detalhes,
        ]);
    }

    private function tipoDe(Model $alvo): string
    {
        return match (true) {
            $alvo instanceof Admin => 'admin',
            $alvo instanceof Questao => 'questao',
            $alvo instanceof ReportQuestao => 'report',
            $alvo instanceof User => $alvo->ehResponsavel() ? 'responsavel' : 'aluno',
            default => strtolower(class_basename($alvo)),
        };
    }
}
