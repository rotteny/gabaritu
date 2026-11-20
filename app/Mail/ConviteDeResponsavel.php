<?php

namespace App\Mail;

use App\Enums\Parentesco;
use App\Enums\Serie;
use App\Enums\TipoConta;
use App\Enums\TipoConvite;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConviteDeResponsavel extends Mailable
{
    public function __construct(
        public readonly ConviteResponsavel $convite,
        public readonly User $aluno,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        $apelido = $this->aluno->apelido;

        return new Envelope(subject: match ($this->tipo()) {
            TipoConvite::Autorizacao, TipoConvite::CoresponsavelAluno => "{$apelido} pediu que você seja responsável no Gabaritu",
            TipoConvite::Coresponsavel => "Convite para acompanhar {$apelido} no Gabaritu",
            TipoConvite::Transferencia => "Convite para assumir a responsabilidade por {$apelido} no Gabaritu",
            default => throw new \LogicException('Pedido do responsável usa PedidoDeResponsavelAoAluno.'),
        });
    }

    public function content(): Content
    {
        return new Content(
            markdown: match ($this->tipo()) {
                TipoConvite::Autorizacao, TipoConvite::CoresponsavelAluno => 'mail.responsavel.convite-solicitacao',
                TipoConvite::Coresponsavel => 'mail.responsavel.convite-coresponsavel',
                TipoConvite::Transferencia => 'mail.responsavel.convite-transferencia',
                default => throw new \LogicException('Pedido do responsável usa PedidoDeResponsavelAoAluno.'),
            },
            with: [
                'apelido' => $this->aluno->apelido,
                'serie' => Serie::tryFrom((string) $this->aluno->serie)?->rotulo(),
                'temConta' => $temConta = $this->destinatarioTemConta(),
                'url' => $this->url($temConta),
                'urlTermos' => url('/termos'),
                'urlPrivacidade' => url('/privacidade'),
                'validade' => $this->convite->expira_em->timezone(config('gabaritu.fuso'))->format('d/m/Y'),
                'parentesco' => $this->parentescoPorExtenso(),
                'emailContato' => config('gabaritu.email_contato'),
            ],
        );
    }

    private function destinatarioTemConta(): bool
    {
        return User::query()->where('tipo', TipoConta::Responsavel->value)
            ->whereRaw('lower(email) = ?', [mb_strtolower($this->convite->email)])->exists();
    }

    private function url(bool $temConta): string
    {
        $convite = "/responsavel/convite/{$this->token}";

        return $temConta ? url('/responsavel/entrar?redirect='.urlencode($convite)) : url($convite);
    }

    private function tipo(): TipoConvite
    {
        return TipoConvite::from($this->convite->tipo);
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
