<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        if ($user->ehResponsavel()) {
            throw ValidationException::withMessages(['email' => trans(PasswordBroker::INVALID_TOKEN)]);
        }

        Validator::make([
            'senha' => $input['password'] ?? null,
            'senha_confirmation' => $input['password_confirmation'] ?? null,
        ], [
            'senha' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Quem pediu a recuperação pode estar tirando um invasor da conta: todas as sessões caem.
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
