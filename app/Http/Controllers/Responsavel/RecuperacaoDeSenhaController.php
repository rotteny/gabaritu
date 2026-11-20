<?php

namespace App\Http\Controllers\Responsavel;

use App\Concerns\PasswordValidationRules;
use App\Enums\TipoConta;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Recuperação de senha do responsável, separada da do aluno. A resposta é igual para e-mail existente ou não. */
class RecuperacaoDeSenhaController extends Controller
{
    use PasswordValidationRules;

    private const string STATUS_ENVIO = 'Se o e-mail estiver cadastrado, enviamos o link para redefinir a senha.';

    public function esqueci(Request $request): Response
    {
        return Inertia::render('responsavel/EsqueciSenha', ['status' => $request->session()->get('status')]);
    }

    public function enviar(Request $request): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink($this->credenciais($dados['email']));

        return back()->with('status', self::STATUS_ENVIO);
    }

    public function formulario(Request $request, string $token): Response
    {
        return Inertia::render('responsavel/RedefinirSenha', ['email' => $request->query('email'), 'token' => $token]);
    }

    public function redefinir(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'senha' => $this->passwordRules(),
        ]);

        $status = Password::reset(
            [...$this->credenciais($dados['email']), 'token' => $dados['token'], 'password' => $dados['senha'], 'password_confirmation' => $request->input('senha_confirmation')],
            function (User $responsavel, string $senha): void {
                $responsavel->forceFill(['password' => $senha, 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $responsavel->id)->delete();
                event(new PasswordReset($responsavel));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => trans($status)]);
        }

        return redirect('/responsavel/entrar')->with('status', 'Senha redefinida. Entre com a nova senha.');
    }

    /** @return array{email: string, tipo: string} */
    private function credenciais(string $email): array
    {
        return ['email' => mb_strtolower(trim($email)), 'tipo' => TipoConta::Responsavel->value];
    }
}
