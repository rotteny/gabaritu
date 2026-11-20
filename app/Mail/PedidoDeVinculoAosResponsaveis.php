<?php

namespace App\Mail;

use App\Enums\Parentesco;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Support\EmailMascarado;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para os responsáveis atuais: alguém pediu para entrar como responsável. Um deles precisa aprovar no painel. */
class PedidoDeVinculoAosResponsaveis extends Mailable
{
    public function __construct(
        public readonly ConviteResponsavel $convite,
        public readonly User $aluno,
        public readonly User $solicitante,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pedido para ser responsável por {$this->aluno->apelido} no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.responsavel.pedido-aviso-responsaveis', with: [
            'apelido' => $this->aluno->apelido,
            'nome' => $this->solicitante->apelido,
            'parentesco' => $this->convite->parentesco === Parentesco::Outro->value
                ? (string) ($this->convite->parentesco_outro ?: 'outro') : $this->convite->parentesco,
            'emailMascarado' => str_replace('*', '\*', EmailMascarado::de($this->solicitante->email)),
            'url' => url('/responsavel'),
            'validade' => $this->convite->expira_em->timezone(config('gabaritu.fuso'))->format('d/m/Y'),
            'emailContato' => config('gabaritu.email_contato'),
        ]);
    }
}
