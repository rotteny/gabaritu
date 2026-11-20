<?php

namespace App\Domain\Admin;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Login do admin: mensagem genérica, tempo parecido com e sem conta e limite por IP e por e-mail. */
class Autenticador
{
    private const int JANELA_EM_SEGUNDOS = 60;

    private const string HASH_FALSO = '$2y$12$mnZZTPv.HUjHscvSCi0n5uMfcLdLZ8VVJqcWlR6DEMW./COazXFrG';

    public function entrar(string $email, string $senha, string $ip): Admin
    {
        $email = mb_strtolower(trim($email));
        $this->limitar($email, $ip);

        $admin = Admin::query()->where('email', $email)->first();
        $confere = Hash::check($senha, $admin instanceof Admin ? $admin->password : self::HASH_FALSO);

        if (! $admin instanceof Admin || ! $confere) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        Auth::guard('admin')->login($admin);

        return $admin;
    }

    /** Conta a tentativa (certa ou errada): 5 por minuto por IP e por e-mail. */
    private function limitar(string $email, string $ip): void
    {
        $maximo = (int) config('gabaritu.admin.login_tentativas_por_minuto');

        foreach (['admin-login-ip|'.$ip, 'admin-login-email|'.hash('sha256', $email)] as $chave) {
            if (RateLimiter::tooManyAttempts($chave, $maximo)) {
                throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
            }
            RateLimiter::hit($chave, self::JANELA_EM_SEGUNDOS);
        }
    }
}
