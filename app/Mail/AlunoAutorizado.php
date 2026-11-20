<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AlunoAutorizado extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly User $responsavel) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Autorização confirmada: a sua conta no Gabaritu está liberada');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.aluno-autorizado', with: [
            'apelido' => $this->aluno->apelido,
            'responsavel' => $this->responsavel->apelido,
            'url' => url('/entrar'),
        ]);
    }
}
