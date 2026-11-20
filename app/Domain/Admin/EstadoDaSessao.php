<?php

namespace App\Domain\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Session\Session;

/** O que a sessão do admin lembra: atividade (timeout), 2FA cumprido e reconfirmação de TOTP para ações sensíveis. */
class EstadoDaSessao
{
    private const string ATIVIDADE = 'admin.atividade_em';

    private const string DOIS_FATORES = 'admin.dois_fatores_em';

    private const string RECONFIRMADO = 'admin.reconfirmado_em';

    private const string ASSINATURA_DA_SENHA = 'admin.assinatura_da_senha';

    public function __construct(private readonly Session $sessao) {}

    public function iniciar(Admin $admin): void
    {
        $this->sessao->put(self::ASSINATURA_DA_SENHA, $this->assinaturaDaSenha($admin));
        $this->registrarAtividade();
    }

    public function registrarAtividade(): void
    {
        $this->sessao->put(self::ATIVIDADE, now()->getTimestamp());
    }

    public function inativo(): bool
    {
        $ultima = $this->sessao->get(self::ATIVIDADE);
        $limite = (int) config('gabaritu.admin.inatividade_minutos') * 60;

        return ! is_int($ultima) || now()->getTimestamp() - $ultima > $limite;
    }

    /** A senha mudou (comando `senha`) depois que esta sessão começou: a sessão cai. */
    public function senhaMudou(Admin $admin): bool
    {
        return ! hash_equals((string) $this->sessao->get(self::ASSINATURA_DA_SENHA), $this->assinaturaDaSenha($admin));
    }

    public function marcarDoisFatoresCumprido(): void
    {
        $this->sessao->put(self::DOIS_FATORES, now()->getTimestamp());
        $this->reconfirmar();
    }

    public function doisFatoresCumprido(): bool
    {
        return $this->sessao->has(self::DOIS_FATORES);
    }

    public function reconfirmar(): void
    {
        $this->sessao->put(self::RECONFIRMADO, now()->getTimestamp());
    }

    public function reconfirmadoRecentemente(): bool
    {
        $quando = $this->sessao->get(self::RECONFIRMADO);

        return is_int($quando) && now()->getTimestamp() - $quando <= (int) config('gabaritu.admin.reconfirmacao_minutos') * 60;
    }

    private function assinaturaDaSenha(Admin $admin): string
    {
        return hash_hmac('sha256', $admin->getAuthPassword(), (string) config('app.key'));
    }
}
