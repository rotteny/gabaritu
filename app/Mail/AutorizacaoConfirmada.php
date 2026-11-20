<?php

namespace App\Mail;

use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para o responsável: confirmação e registro do aceite (data e hora, versão dos termos). */
class AutorizacaoConfirmada extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly User $responsavel, public readonly VinculoResponsavel $vinculo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Você autorizou {$this->aluno->apelido} no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.autorizacao-confirmada', with: [
            'apelido' => $this->aluno->apelido,
            'responsavel' => $this->responsavel->apelido,
            'url' => url('/responsavel'),
            'aceitoEm' => $this->vinculo->aceito_em->timezone(config('gabaritu.fuso'))->format('d/m/Y \à\s H:i'),
            'versaoTermos' => $this->vinculo->termos_versao,
        ]);
    }
}
