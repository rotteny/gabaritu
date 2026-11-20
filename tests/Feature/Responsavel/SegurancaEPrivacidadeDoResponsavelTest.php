<?php

use App\Mail\AlunoAutorizado;
use App\Mail\AutorizacaoRecusada;
use App\Mail\AvisoRemocaoPorPendencia;
use App\Mail\ConviteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

describe('rate limit', function () {
    it('limita tentativas de aceite por IP', function () {
        foreach (range(1, 10) as $i) {
            $this->get('/responsavel/convite/token-errado-'.$i)->assertOk();
        }

        $this->get('/responsavel/convite/token-errado-11')->assertStatus(429);
        $this->post('/responsavel/convite/qualquer', dadosDeAceiteNovo())->assertStatus(429);
    });

    it('limita a emissão de convites do responsável', function () {
        Mail::fake();
        $responsavel = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($responsavel)->create();
        $this->actingAs($responsavel);

        foreach (range(1, 10) as $i) {
            // e-mail inválido não envia, mas passa pelo limitador
            $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => 'x', 'tipo' => 'coresponsavel', 'parentesco' => 'pai']);
        }

        $this->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => 'ok@exemplo.test', 'tipo' => 'coresponsavel', 'parentesco' => 'pai'])->assertStatus(429);
    });
});

describe('dados mínimos do responsável', function () {
    it('não tem coluna para CPF, RG, telefone, endereço, data completa, foto ou documento', function () {
        $proibidas = ['cpf', 'rg', 'telefone', 'celular', 'endereco', 'data_nascimento', 'nascimento_em', 'foto', 'documento', 'cep'];
        $colunas = [...Schema::getColumnListing('users'), ...Schema::getColumnListing('vinculos_responsavel'), ...Schema::getColumnListing('convites_responsavel')];

        expect(array_intersect($proibidas, $colunas))->toBe([]);
    });

    it('guarda só o ano de nascimento (inteiro) e o aceite com IP, versões e declarações', function () {
        expect(Schema::getColumnType('users', 'ano_nascimento'))->toBeIn(['int2', 'smallint', 'integer', 'int4']);
        expect(Schema::getColumnListing('vinculos_responsavel'))->toEqualCanonicalizing([
            'id', 'aluno_id', 'responsavel_id', 'parentesco', 'parentesco_outro', 'aceito_em', 'termos_versao',
            'privacidade_versao', 'aceite_ip', 'declara_responsavel', 'aceite_termos', 'created_at', 'updated_at',
        ]);
    });

    it('os campos do responsável não entram na atribuição em massa', function () {
        $usuario = new User;
        $vinculo = new VinculoResponsavel;

        foreach (['tipo', 'status', 'zoeira_responsavel', 'email_responsavel', 'pendente_desde', 'sem_aluno_desde'] as $campo) {
            expect($usuario->isFillable($campo))->toBeFalse($campo);
        }
        expect(array_intersect(['cpf', 'telefone', 'endereco', 'rg', 'documento'], [...$usuario->getFillable(), ...$vinculo->getFillable()]))->toBe([]);
    });

    it('o convite aceito não ignora campos extras como CPF e telefone', function () {
        Mail::fake();
        [, $token] = conviteComToken(['email' => 'mae@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo(['cpf' => '123.456.789-09', 'telefone' => '86999999999', 'endereco' => 'Rua X']))->assertRedirect('/responsavel');

        $responsavel = User::firstWhere('email', 'mae@exemplo.test');
        expect(array_keys($responsavel->getAttributes()))->not->toContain('cpf', 'telefone', 'endereco');
    });
});

describe('termos e versão dos textos', function () {
    it('/termos é pública mesmo com o cadastro fechado', function () {
        config(['gabaritu.cadastro_aberto' => false]);

        $this->get('/termos')->assertOk()->assertInertia(fn ($page) => $page->component('Termos')->where('versao', config('gabaritu.termos_versao')));
        $this->get('/responsavel/convite/x')->assertNotFound();
    });

    it('termos e política usam a mesma versão dos textos', function () {
        expect(config('gabaritu.termos_versao'))->toBe('2026-11-20')->and(config('gabaritu.privacidade_versao'))->toBe('2026-11-20');
    });
});

describe('e-mails', function () {
    it('renderiza os 6 e-mails em pt-BR com a informação clara', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['apelido' => 'Joaozinho', 'serie' => '1em']);
        $responsavel = User::factory()->responsavel()->create(['apelido' => 'Dona Marta']);
        $html = fn (string $tipo) => (new ConviteDeResponsavel(ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => $tipo]), $aluno, 'tok123'))->render();

        expect($html('autorizacao'))->toContain('Joaozinho', '1º ano do ensino médio', '/responsavel/convite/tok123', 'ECA Digital', 'Peixoto');
        expect($html('coresponsavel'))->toContain('Joaozinho', '/responsavel/convite/tok123');
        expect($html('coresponsavel_aluno'))->toContain('pediu que você seja responsável');
        expect($html('transferencia'))->toContain('transferir a responsabilidade');
        expect((new AlunoAutorizado($aluno, $responsavel))->render())->toContain('Dona Marta', 'Peixoto');
        expect((new AutorizacaoRecusada($aluno))->render())->toContain('recusou', 'Peixoto');
        expect((new AvisoRemocaoPorPendencia($aluno, 3))->render())->toContain('3 dias', 'Peixoto');
    });
});
