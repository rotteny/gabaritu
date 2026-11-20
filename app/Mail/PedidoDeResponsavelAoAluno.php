<?php

namespace App\Mail;

use App\Enums\Parentesco;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Para o aluno: um adulto pediu para ser responsável por ele. Sem conta, o link leva ao cadastro. */
class PedidoDeResponsavelAoAluno extends Mailable
{
    public function __construct(
        public readonly ConviteResponsavel $convite,
        public readonly User $solicitante,
        public readonly ?string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->solicitante->apelido} quer ser seu responsável no Gabaritu");
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->token === null ? 'mail.responsavel.pedido-aluno-existente' : 'mail.responsavel.pedido-aluno-novo',
            with: [
                'nome' => $this->solicitante->apelido,
                'parentesco' => $this->parentescoPorExtenso(),
                'url' => $this->token === null ? url('/conta') : url('/cadastro?convite='.$this->token),
                'validade' => $this->convite->expira_em->timezone(config('gabaritu.fuso'))->format('d/m/Y'),
                'precisaAprovacao' => $this->convite->exige_aprovacao,
                'urlTermos' => url('/termos'),
                'urlPrivacidade' => url('/privacidade'),
                'emailContato' => config('gabaritu.email_contato'),
            ],
        );
    }

    private function parentescoPorExtenso(): string
    {
        return match (Parentesco::from($this->convite->parentesco)) {
            Parentesco::Pai => 'pai',
            Parentesco::Mae => 'mãe',
            Parentesco::Avo => 'avó ou avô',
            Parentesco::Tutor => 'tutor ou guardião legal',
            Parentesco::Outro => (string) ($this->convite->parentesco_outro ?: 'outro responsável'),
        };
    }
}
