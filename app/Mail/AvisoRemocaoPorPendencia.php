<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AvisoRemocaoPorPendencia extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly int $diasRestantes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Sua conta no Gabaritu será apagada em {$this->diasRestantes} dias");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.aviso-remocao', with: [
            'apelido' => $this->aluno->apelido,
            'dias' => $this->diasRestantes,
            'url' => url('/aguardando-responsavel'),
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
