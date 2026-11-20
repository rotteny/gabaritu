<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para o responsável: o aluno que fez 18 anos encerrou o acompanhamento. O vínculo acabou e não é restaurado. */
class AcompanhamentoEncerrado extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly User $responsavel) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->aluno->apelido} encerrou o acompanhamento no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.acompanhamento-encerrado', with: [
            'apelido' => $this->aluno->apelido,
            'url' => url('/responsavel'),
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
