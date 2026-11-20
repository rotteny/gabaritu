<?php

namespace App\Mail;

use App\Enums\Parentesco;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Support\EmailMascarado;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para os responsáveis atuais: o aluno convidou mais um responsável. Não precisa de aprovação, mas dá para cancelar no painel. */
class ConviteDoAlunoAosResponsaveis extends Mailable
{
    public function __construct(public readonly ConviteResponsavel $convite, public readonly User $aluno) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->aluno->apelido} convidou mais um responsável no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.convite-aluno-aviso-responsaveis', with: [
            'apelido' => $this->aluno->apelido,
            'parentesco' => $this->convite->parentesco === Parentesco::Outro->value
                ? (string) ($this->convite->parentesco_outro ?: 'outro') : $this->convite->parentesco,
            'emailMascarado' => str_replace('*', '\*', EmailMascarado::de($this->convite->email)),
            'url' => url('/responsavel'),
            'validade' => $this->convite->expira_em->timezone(config('gabaritu.fuso'))->format('d/m/Y'),
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
