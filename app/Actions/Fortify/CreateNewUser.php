<?php

namespace App\Actions\Fortify;

use App\Concerns\ParentescoValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Responsavel\ConviteDoAluno;
use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\EmailDeResponsavel;
use App\Mail\CadastroConfirmado;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use ParentescoValidationRules;
    use ProfileValidationRules;

    public function __construct(private readonly ConviteDoAluno $convites, private readonly EmailDeResponsavel $emails) {}

    private const string ZOEIRA_INICIAL = 'fofo';

    /** @param  array<string, mixed>  $input */
    public function create(array $input): User
    {
        $menor = $this->ehMenor($input);
        $dados = Validator::make($input, [
            'apelido' => $this->apelidoRules(),
            'email' => $this->emailRules(),
            'senha' => $this->senhaRules(),
            'ano_nascimento' => $this->anoNascimentoRules(),
            'serie' => $this->serieRules(),
            'aceite_privacidade' => ['accepted'],
            ...($menor ? $this->regrasDoResponsavel((string) ($input['email'] ?? '')) : []),
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
            'zoeira' => self::ZOEIRA_INICIAL,
        ]);
        // Fora do fillable de propósito: só o cadastro grava o aceite.
        $aluno->forceFill([
            'privacidade_aceita_em' => now(),
            'privacidade_versao' => (string) config('gabaritu.privacidade_versao'),
        ])->save();

        $convite = $menor ? $this->convites->enviarPrimeiro(
            $aluno,
            $dados['email_responsavel'],
            DadosDeParentesco::de($dados['parentesco_responsavel'], $dados['parentesco_outro'] ?? null),
        ) : null;

        Mail::to($aluno->email)->send(new CadastroConfirmado($aluno, $convite));

        return $aluno;
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
