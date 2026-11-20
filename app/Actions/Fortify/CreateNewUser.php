<?php

namespace App\Actions\Fortify;

use App\Concerns\ParentescoValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Responsavel\ConviteDoAluno;
use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\EmailDeResponsavel;
use App\Domain\Responsavel\PedidoDeVinculo;
use App\Mail\CadastroConfirmado;
use App\Models\ConviteResponsavel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use ParentescoValidationRules;
    use ProfileValidationRules;

    public function __construct(private readonly ConviteDoAluno $convites, private readonly EmailDeResponsavel $emails, private readonly PedidoDeVinculo $pedidos) {}

    private const string ZOEIRA_INICIAL = 'fofo';

    /** @param  array<string, mixed>  $input */
    public function create(array $input): User
    {
        $menor = $this->ehMenor($input);
        $convite = $this->pedidos->localizarParaCadastro(is_string($input['convite'] ?? null) ? $input['convite'] : null);
        $vincular = $convite !== null && $this->conviteValeParaEsteCadastro($convite, $input, $menor) && filter_var($input['confirma_responsavel'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dados = Validator::make($input, [
            'apelido' => $this->apelidoRules(),
            'email' => $this->emailRules(),
            'senha' => $this->senhaRules(),
            'ano_nascimento' => $this->anoNascimentoRules(),
            'serie' => $this->serieRules(),
            'aceite_privacidade' => ['accepted'],
            ...($menor && ! $vincular ? $this->regrasDoResponsavel((string) ($input['email'] ?? '')) : []),
        ], [
            'aceite_privacidade.accepted' => 'Para criar a conta, é preciso aceitar a política de privacidade.',
            'email_responsavel.required' => 'Informe o e-mail de um responsável: quem tem menos de 18 anos precisa da autorização dele.',
        ])->validate();

        $aluno = new User([
            'apelido' => $dados['apelido'],
            'email' => $dados['email'],
            'password' => $dados['senha'],
            'ano_nascimento' => (int) $dados['ano_nascimento'],
            'serie' => $dados['serie'],
            'avatar' => config('gabaritu.avatar_padrao'),
            'zoeira' => self::ZOEIRA_INICIAL,
        ]);
        // Fora do fillable de propósito: só o cadastro grava o aceite.
        $aluno->forceFill([
            'privacidade_aceita_em' => now(),
            'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
        ])->save();

        $conviteDeAutorizacao = $menor && ! $vincular ? $this->convites->enviarPrimeiro(
            $aluno,
            $dados['email_responsavel'],
            DadosDeParentesco::de($dados['parentesco_responsavel'], $dados['parentesco_outro'] ?? null),
        ) : null;
        $this->resolverPedido($convite, $vincular, $aluno);

        Mail::to($aluno->email)->send(new CadastroConfirmado($aluno, $conviteDeAutorizacao));

        return $aluno;
    }

    /** @param  array<string, mixed>  $input */
    private function conviteValeParaEsteCadastro(ConviteResponsavel $convite, array $input, bool $menor): bool
    {
        if (! $menor) {
            throw ValidationException::withMessages(['convite' => 'Maiores de idade não precisam de responsável.']);
        }

        if (EmailDeResponsavel::normalizar((string) ($input['email'] ?? '')) !== EmailDeResponsavel::normalizar($convite->email)) {
            throw ValidationException::withMessages(['convite' => 'Este convite foi enviado para outro e-mail.']);
        }

        return true;
    }

    private function resolverPedido(?ConviteResponsavel $convite, bool $vincular, User $aluno): void
    {
        if ($convite === null) {
            return;
        }

        $vincular ? $this->pedidos->aceitarNoCadastro($convite, $aluno) : $this->pedidos->recusar($convite);
    }

    /** @param  array<string, mixed>  $input */
    private function ehMenor(array $input): bool
    {
        $ano = $input['ano_nascimento'] ?? null;

        return is_numeric($ano) && User::idadePorAno((int) $ano) < User::IDADE_MAIORIDADE;
    }

    /** @return array<string, array<int, mixed>> */
    private function regrasDoResponsavel(string $emailDoAluno): array
    {
        return [
            'email_responsavel' => ['required', 'string', 'email', 'max:255', function (string $atributo, mixed $valor, \Closure $falhar) use ($emailDoAluno): void {
                if (is_string($valor) && ! $this->emails->usavel($valor, $emailDoAluno)) {
                    $falhar(EmailDeResponsavel::MENSAGEM);
                }
            }],
            ...$this->parentescoRules('parentesco_responsavel', 'parentesco_outro'),
        ];
    }

    /** @return array<int, mixed> */
    private function senhaRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
