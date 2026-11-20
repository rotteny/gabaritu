<?php

use App\Mail\AvisoRemocaoPorPendencia;
use App\Models\ConviteResponsavel;
use App\Models\DesempenhoAssunto;
use App\Models\Tentativa;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('avisa o pendente 3 dias antes de apagar, sem apagar no mesmo dia', function () {
    $recente = User::factory()->pendenteHaDias(10)->create();
    $quase = User::factory()->pendenteHaDias(27)->create(['email' => 'quase@exemplo.test']);

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

    Mail::assertSent(AvisoRemocaoPorPendencia::class, 1);
    Mail::assertSent(AvisoRemocaoPorPendencia::class, fn ($m) => $m->hasTo('quase@exemplo.test'));
    expect($quase->fresh()->aviso_remocao_em)->not->toBeNull()->and($recente->fresh()->aviso_remocao_em)->toBeNull();
    expect(User::find($quase->id))->not->toBeNull();

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();
    Mail::assertSent(AvisoRemocaoPorPendencia::class, 1);
});

it('apaga o pendente há mais de 30 dias depois do aviso, com os dados dele', function () {
    $aluno = User::factory()->pendenteHaDias(31)->create(['aviso_remocao_em' => now()->subDays(4)]);
    $c = conteudoDeTeste(2);
    DesempenhoAssunto::query()->insert(['user_id' => $aluno->id, 'assunto_id' => $c['assunto']->id, 'theta' => 0.5, 'n_primeiras' => 3]);
    $ativo = User::factory()->comResponsavel()->create();

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

    expect(User::find($aluno->id))->toBeNull()->and(User::find($ativo->id))->not->toBeNull()
        ->and(Tentativa::where('user_id', $aluno->id)->count())->toBe(0);
});

it('não apaga quem passou de 30 dias mas ainda não foi avisado há 3 dias', function () {
    $aluno = User::factory()->pendenteHaDias(40)->create();

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

    expect(User::find($aluno->id))->not->toBeNull()->and($aluno->fresh()->aviso_remocao_em)->not->toBeNull();
    $this->travel(4)->days();
    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();
    expect(User::find($aluno->id))->toBeNull();
});

it('apaga convites vencidos e mantém os válidos', function () {
    $vencido = ConviteResponsavel::factory()->vencido()->create();
    $valido = ConviteResponsavel::factory()->create();

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

    expect(ConviteResponsavel::find($vencido->id))->toBeNull()->and(ConviteResponsavel::find($valido->id))->not->toBeNull();
});

it('apaga responsável sem aluno há 30 dias e mantém os demais', function () {
    $velho = User::factory()->responsavel()->create(['sem_aluno_desde' => now()->subDays(31)]);
    $recente = User::factory()->responsavel()->create(['sem_aluno_desde' => now()->subDays(5)]);
    $comAluno = User::factory()->responsavel()->create(['sem_aluno_desde' => now()->subDays(60)]);
    User::factory()->comResponsavel($comAluno)->create();

    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

    expect(User::find($velho->id))->toBeNull()->and(User::find($recente->id))->not->toBeNull()->and(User::find($comAluno->id))->not->toBeNull();
});

it('está agendado todo dia', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('gabaritu:limpar-pendentes')->assertSuccessful();
});
