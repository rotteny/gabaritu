<?php

namespace App\Console\Commands;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\DoisFatores;
use App\Models\Admin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * O admin só existe por aqui: não há cadastro nem "esqueci a senha" no painel.
 * A senha nunca vem por argumento (iria para o histórico do shell e para a lista de processos): só por prompt oculto.
 */
#[Signature('gabaritu:admin {acao : criar | senha | 2fa-reset} {email : E-mail do admin}')]
#[Description('Cria o admin, troca a senha ou reinicia o 2FA do painel (senha sempre por prompt oculto).')]
class GerenciarAdmin extends Command
{
    public function handle(Auditoria $auditoria, DoisFatores $doisFatores): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        return match ($this->argument('acao')) {
            'criar' => $this->criar($email, $auditoria),
            'senha' => $this->trocarSenha($email, $auditoria),
            '2fa-reset' => $this->resetarDoisFatores($email, $auditoria, $doisFatores),
            default => $this->falhar('Ação desconhecida. Use: criar, senha ou 2fa-reset.'),
        };
    }

    private function criar(string $email, Auditoria $auditoria): int
    {
        if (Validator::make(['email' => $email], ['email' => ['required', 'email:rfc']])->fails()) {
            return $this->falhar('E-mail inválido.');
        }
        if (Admin::query()->where('email', $email)->exists()) {
            return $this->falhar('Já existe um admin com este e-mail. Para trocar a senha: gabaritu:admin senha '.$email);
        }

        $senha = $this->pedirSenha();
        if ($senha === null) {
            return self::FAILURE;
        }

        $admin = Admin::create(['email' => $email, 'password' => $senha]);
        $auditoria->registrar($admin, AcaoDeAuditoria::AdminCriado, $admin);

        $this->info('Admin criado. No primeiro login, o painel obriga a configurar o 2FA: entre em /'.config('gabaritu.admin_prefixo').'/entrar.');

        return self::SUCCESS;
    }

    private function trocarSenha(string $email, Auditoria $auditoria): int
    {
        $admin = Admin::query()->where('email', $email)->first();
        if ($admin === null) {
            return $this->falhar('Admin não encontrado.');
        }

        $senha = $this->pedirSenha();
        if ($senha === null) {
            return self::FAILURE;
        }

        $admin->forceFill(['password' => $senha])->save();
        $auditoria->registrar($admin, AcaoDeAuditoria::SenhaTrocada, $admin);

        $this->info('Senha trocada. As sessões abertas deste admin caem no próximo clique.');

        return self::SUCCESS;
    }

    private function resetarDoisFatores(string $email, Auditoria $auditoria, DoisFatores $doisFatores): int
    {
        $admin = Admin::query()->where('email', $email)->first();
        if ($admin === null) {
            return $this->falhar('Admin não encontrado.');
        }

        $doisFatores->resetar($admin);
        $auditoria->registrar($admin, AcaoDeAuditoria::DoisFatoresResetado, $admin);

        $this->info('2FA reiniciado. No próximo login o painel pede um novo QR e novos códigos de recuperação.');

        return self::SUCCESS;
    }

    private function pedirSenha(): ?string
    {
        $senha = (string) $this->secret('Senha (não aparece na tela)');
        // Decisão do dono (20/11): mesma linha dos alunos, 8 caracteres + não vazada; o 2FA obrigatório é a proteção principal.
        $regra = Password::min((int) config('gabaritu.admin.senha_minima'))->uncompromised();

        $validador = Validator::make(['senha' => $senha], ['senha' => ['required', $regra]]);
        if ($validador->fails()) {
            $this->falhar($validador->errors()->first('senha'));

            return null;
        }

        if ($senha !== (string) $this->secret('Repita a senha')) {
            $this->falhar('As senhas não conferem.');

            return null;
        }

        return $senha;
    }

    private function falhar(string $mensagem): int
    {
        $this->error($mensagem);

        return self::FAILURE;
    }
}
