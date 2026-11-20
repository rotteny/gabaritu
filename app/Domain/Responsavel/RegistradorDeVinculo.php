<?php

namespace App\Domain\Responsavel;

use App\Enums\StatusConta;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Carbon\CarbonInterface;

/** Grava o vínculo ativo com o registro do aceite (prova da autorização) e libera o aluno. */
class RegistradorDeVinculo
{
    public function registrar(
        User $aluno,
        User $responsavel,
        DadosDeParentesco $parentesco,
        CarbonInterface $aceitoEm,
        ?string $ip,
        ?string $termosVersao = null,
    ): VinculoResponsavel {
        $vinculo = VinculoResponsavel::query()->updateOrCreate(
            ['aluno_id' => $aluno->id, 'responsavel_id' => $responsavel->id],
            [
                'parentesco' => $parentesco->parentesco->value,
                'parentesco_outro' => $parentesco->outro,
                'aceito_em' => $aceitoEm,
                'termos_versao' => $termosVersao ?? (string) config('gabaritu.termos_versao'),
                'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
                'aceite_ip' => $ip,
                'declara_responsavel' => true,
                'aceite_termos' => true,
                'status' => VinculoResponsavel::ATIVO,
            ],
        );
        $vinculo->forceFill(['suspenso_em' => null])->save();
        $responsavel->forceFill(['sem_aluno_desde' => null])->save();

        return $vinculo;
    }

    /** @return bool verdadeiro se o aluno estava aguardando e foi liberado agora */
    public function liberarAluno(User $aluno): bool
    {
        if (! $aluno->aguardaResponsavel()) {
            return false;
        }

        $aluno->forceFill(['status' => StatusConta::Ativo->value, 'pendente_desde' => null, 'aviso_remocao_em' => null])->save();

        return true;
    }
}
