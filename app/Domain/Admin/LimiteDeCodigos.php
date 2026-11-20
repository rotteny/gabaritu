<?php

namespace App\Domain\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Limite de tentativas de código (TOTP ou recuperação): 5 por minuto por admin e por IP, contando acertos e erros. */
class LimiteDeCodigos
{
    private const int JANELA_EM_SEGUNDOS = 60;

    public function registrar(Admin $admin, string $ip, string $campo = 'codigo'): void
    {
        $maximo = (int) config('gabaritu.admin.login_tentativas_por_minuto');

        foreach (['admin-codigo-admin|'.$admin->id, 'admin-codigo-ip|'.$ip] as $chave) {
            if (RateLimiter::tooManyAttempts($chave, $maximo)) {
                throw ValidationException::withMessages([$campo => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
            }
            RateLimiter::hit($chave, self::JANELA_EM_SEGUNDOS);
        }
    }
}
