<?php

namespace App\Mail;

use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Support\EmailMascarado;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Boas-vindas logo após o cadastro: adulto entra direto; menor espera o responsável. */
class CadastroConfirmado extends Mailable
{
    public function __construct(public readonly User $aluno, public readonly ?ConviteResponsavel $convite = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->convite === null
            ? 'Sua conta no Gabaritu está pronta'
            : 'Falta a autorização do seu responsável para usar o Gabaritu');
    }

    public function content(): Content
    {
        $menor = $this->convite !== null;

        return new Content(
            markdown: $menor ? 'mail.responsavel.cadastro-menor' : 'mail.responsavel.cadastro-adulto',
            with: [
                'apelido' => $this->aluno->apelido,
                'url' => url($menor ? '/aguardando-responsavel' : '/entrar'),
                'emailResponsavel' => EmailMascarado::de((string) $this->aluno->email_responsavel),
                'validade' => $this->convite?->expira_em->timezone(config('gabaritu.fuso'))->format('d/m/Y'),
            ],
        );
    }
}
