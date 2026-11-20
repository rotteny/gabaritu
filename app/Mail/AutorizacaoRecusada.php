<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AutorizacaoRecusada extends Mailable
{
    public function __construct(public readonly User $aluno) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu responsável não autorizou a sua conta no Gabaritu');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.autorizacao-recusada', with: [
            'apelido' => $this->aluno->apelido,
            'url' => url('/aguardando-responsavel'),
        ]);
    }
}
