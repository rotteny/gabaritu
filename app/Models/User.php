<?php

namespace App\Models;

use App\Enums\Nivel;
use App\Enums\StatusConta;
use App\Enums\TipoConta;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $apelido
 * @property string $email
 * @property int|null $ano_nascimento
 * @property string|null $serie
 * @property string $zoeira
 * @property bool $som
 * @property bool $musica
 * @property bool $reduzir_movimento
 * @property string|null $avatar
 * @property string|null $ultima_materia
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $privacidade_aceita_em
 * @property string|null $privacidade_versao
 * @property string $tipo
 * @property string $status
 * @property string|null $zoeira_responsavel
 * @property string|null $email_responsavel
 * @property string|null $parentesco_responsavel
 * @property string|null $parentesco_responsavel_outro
 * @property Carbon|null $pendente_desde
 * @property Carbon|null $aviso_remocao_em
 * @property Carbon|null $sem_aluno_desde
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['apelido', 'email', 'password', 'ano_nascimento', 'serie', 'zoeira', 'som', 'musica', 'reduzir_movimento', 'ultima_materia', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const int IDADE_MAIORIDADE = 18;

    /** @var array<string, mixed> */
    protected $attributes = ['tipo' => 'aluno', 'status' => 'ativo'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'som' => 'boolean',
            'musica' => 'boolean',
            'reduzir_movimento' => 'boolean',
            'ano_nascimento' => 'integer',
            'privacidade_aceita_em' => 'datetime',
            'pendente_desde' => 'datetime',
            'aviso_remocao_em' => 'datetime',
            'sem_aluno_desde' => 'datetime',
        ];
    }

    public static function idadePorAno(int $anoNascimento): int
    {
        return (int) now(config('gabaritu.fuso'))->format('Y') - $anoNascimento - 1;
    }

    public function idade(): int
    {
        return self::idadePorAno((int) $this->ano_nascimento);
    }

    public function ehResponsavel(): bool
    {
        return $this->tipo === TipoConta::Responsavel->value;
    }

    public function aguardaResponsavel(): bool
    {
        return $this->status === StatusConta::AguardandoResponsavel->value;
    }

    public function precisaDeResponsavel(): bool
    {
        return ! $this->ehResponsavel() && $this->idade() < self::IDADE_MAIORIDADE;
    }

    /** Teto efetivo: o menor entre o da idade e o escolhido pelo responsável. */
    public function zoeiraMaxima(): Nivel
    {
        $daIdade = $this->zoeiraMaximaPorIdade();
        $doResponsavel = Nivel::tryFrom((string) $this->zoeira_responsavel);

        return $doResponsavel === null ? $daIdade : $daIdade->limitadoPor($doResponsavel);
    }

    public function zoeiraMaximaPorIdade(): Nivel
    {
        return Nivel::tetoParaIdade($this->idade());
    }

    /** Onde o usuário cai depois de entrar. */
    public function caminhoInicial(): string
    {
        return match (true) {
            $this->ehResponsavel() => '/responsavel',
            $this->aguardaResponsavel() => '/aguardando-responsavel',
            default => '/inicio',
        };
    }

    public function zoeiraEfetiva(): Nivel
    {
        return (Nivel::tryFrom($this->zoeira) ?? Nivel::Fofo)->limitadoPor($this->zoeiraMaxima());
    }

    /** @return array<string, mixed> */
    public function paraCompartilhar(): array
    {
        return [
            'id' => $this->id,
            'apelido' => $this->apelido,
            'serie' => $this->serie,
            'avatar' => $this->avatar,
            'idade' => $this->idade(),
            'tipo' => $this->tipo,
            'status' => $this->status,
            'preferencias' => [
                'zoeira' => $this->zoeiraEfetiva()->value,
                'zoeira_maxima' => $this->zoeiraMaxima()->value,
                'som' => $this->som,
                'musica' => $this->musica,
                'reduzir_movimento' => $this->reduzir_movimento,
                'ultima_materia' => $this->ultima_materia,
            ],
        ];
    }

    /** O link do e-mail leva ao fluxo de recuperação do tipo certo (aluno ou responsável). */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $notificacao = new ResetPassword($token);
        $notificacao->createUrlUsing(function (self $usuario, string $token): string {
            $caminho = $usuario->ehResponsavel() ? '/responsavel/redefinir-senha/' : '/redefinir-senha/';

            return url($caminho.$token).'?email='.urlencode($usuario->getEmailForPasswordReset());
        });

        $this->notify($notificacao);
    }

    /** @return HasMany<Tentativa, $this> */
    public function tentativas(): HasMany
    {
        return $this->hasMany(Tentativa::class);
    }

    /** @return HasMany<Resposta, $this> */
    public function respostas(): HasMany
    {
        return $this->hasMany(Resposta::class);
    }

    /** @return HasMany<VinculoResponsavel, $this> vínculos em que este usuário é o aluno */
    public function vinculosComResponsaveis(): HasMany
    {
        return $this->hasMany(VinculoResponsavel::class, 'aluno_id');
    }

    /** @return HasMany<VinculoResponsavel, $this> vínculos em que este usuário é o responsável */
    public function vinculosComAlunos(): HasMany
    {
        return $this->hasMany(VinculoResponsavel::class, 'responsavel_id');
    }

    /** @return HasMany<VinculoResponsavel, $this> */
    public function vinculosAtivosComResponsaveis(): HasMany
    {
        return $this->vinculosComResponsaveis()->where('status', VinculoResponsavel::ATIVO);
    }

    /** @return HasMany<VinculoResponsavel, $this> */
    public function vinculosAtivosComAlunos(): HasMany
    {
        return $this->vinculosComAlunos()->where('status', VinculoResponsavel::ATIVO);
    }

    /** @return BelongsToMany<User, $this> responsáveis com vínculo ativo */
    public function responsaveis(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'vinculos_responsavel', 'aluno_id', 'responsavel_id')->wherePivot('status', VinculoResponsavel::ATIVO);
    }

    /** @return BelongsToMany<User, $this> alunos com vínculo ativo */
    public function alunos(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'vinculos_responsavel', 'responsavel_id', 'aluno_id')->wherePivot('status', VinculoResponsavel::ATIVO);
    }

    /** @return HasMany<ConviteResponsavel, $this> */
    public function convitesDeResponsavel(): HasMany
    {
        return $this->hasMany(ConviteResponsavel::class, 'aluno_id');
    }
}
