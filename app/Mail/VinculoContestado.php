<?php

namespace App\Mail;

use App\Models\User;
use App\Models\VinculoResponsavel;
use App\Support\EmailMascarado;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para os outros responsáveis e para o contato do Gabaritu: o aluno disse que aquela pessoa não é responsável dele. */
class VinculoContestado extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly User $contestado, public readonly VinculoResponsavel $vinculo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->aluno->apelido} contestou um responsável no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.vinculo-contestado', with: [
            'apelido' => $this->aluno->apelido,
            'nome' => $this->contestado->apelido,
            'parentesco' => $this->vinculo->parentesco === 'outro' ? (string) ($this->vinculo->parentesco_outro ?: 'outro') : $this->vinculo->parentesco,
            'emailMascarado' => str_replace('*', '\*', EmailMascarado::de($this->contestado->email)),
            'url' => url('/responsavel'),
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
