<?php

use App\Models\Admin;
use PragmaRX\Google2FA\Google2FA;

/** Admin com 2FA já confirmado. @return array{0: Admin, 1: string} o admin e o segredo TOTP */
function adminComDoisFatores(string $email = 'dono@gabaritu.test'): array
{
    $segredo = (new Google2FA)->generateSecretKey(32);
    $admin = Admin::create(['email' => $email, 'password' => 'Senha-do-admin-123']);
    $admin->forceFill(['dois_fatores_segredo' => $segredo, 'confirmado_em' => now()])->save();

    return [$admin, $segredo];
}

function codigoTotp(string $segredo): string
{
    return (new Google2FA)->getCurrentOtp($segredo);
}

/** Sessão de admin já com login, 2FA cumprido e reconfirmação em dia (sem passar pelo formulário). */
function comoAdmin(Admin $admin, bool $reconfirmado = true): void
{
    $agora = now()->getTimestamp();

    test()->actingAs($admin, 'admin')->withSession([
        'admin.atividade_em' => $agora,
        'admin.dois_fatores_em' => $agora,
        'admin.reconfirmado_em' => $reconfirmado ? $agora : $agora - 3600,
        'admin.assinatura_da_senha' => hash_hmac('sha256', $admin->getAuthPassword(), (string) config('app.key')),
    ]);
}
