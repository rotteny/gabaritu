<?php

use App\Mail\PedidoDeVinculoAosResponsaveis;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;

// Achados da revisão de segurança da branch `responsavel` (NEZUKO).

beforeEach(fn () => Mail::fake());

/** Aluno de 15 anos com dois responsáveis ativos: [aluno, emissor, outro]. */
function alunoComEmissorEOutro(): array
{
    $emissor = User::factory()->responsavel()->create(['email' => 'emissor@exemplo.test']);
    $outro = User::factory()->responsavel()->create(['email' => 'outro@exemplo.test']);
    $aluno = User::factory()->comIdade(15)->comResponsavel($emissor)->create();
    VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id, 'responsavel_id' => $outro->id]);

    return [$aluno, $emissor, $outro];
}

/** @return array{0: ConviteResponsavel, 1: string} */
function conviteDoEmissor(User $aluno, User $emissor, string $tipo): array
{
    return conviteComToken([
        'aluno_id' => $aluno->id, 'solicitante_id' => $emissor->id, 'tipo' => $tipo, 'email' => 'cumplice@exemplo.test',
        'substitui_responsavel_id' => $tipo === 'transferencia' ? $emissor->id : null,
    ]);
}

describe('convite emitido por responsável que perdeu o vínculo', function () {
    it('não vale depois que o aluno contesta quem emitiu', function (string $tipo) {
        [$aluno, $emissor] = alunoComEmissorEOutro();
        [, $token] = conviteDoEmissor($aluno, $emissor, $tipo);

        $this->actingAs($aluno)->post("/conta/responsaveis/{$emissor->id}/contestar")->assertRedirect();
        $this->post('/logout');

        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('convite', null));
        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertInertia(fn ($page) => $page->where('convite', null));

        expect(User::where('email', 'cumplice@exemplo.test')->exists())->toBeFalse()
            ->and(VinculoResponsavel::where('aluno_id', $aluno->id)->where('responsavel_id', $emissor->id)->value('status'))->toBe('suspenso');
    })->with(['coresponsavel', 'transferencia']);

    it('não vale depois que quem emitiu revoga a própria autorização ou apaga a conta', function (string $saida) {
        [$aluno, $emissor] = alunoComEmissorEOutro();
        [, $token] = conviteDoEmissor($aluno, $emissor, 'coresponsavel');

        $this->actingAs($emissor);
        $saida === 'revoga'
            ? $this->delete("/responsavel/alunos/{$aluno->id}/vinculo")->assertRedirect('/responsavel')
            : $this->delete('/responsavel/conta', ['senha' => 'password'])->assertRedirect('/');
        $this->post('/logout');

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertInertia(fn ($page) => $page->where('convite', null));
        expect($aluno->fresh()->responsaveis()->count())->toBe(1);
    })->with(['revoga', 'apaga']);

    it('continua valendo enquanto quem emitiu segue ativo', function () {
        [$aluno, $emissor] = alunoComEmissorEOutro();
        [, $token] = conviteDoEmissor($aluno, $emissor, 'coresponsavel');

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertRedirect('/responsavel');
        expect($aluno->fresh()->responsaveis()->count())->toBe(3);
    });
});

describe('pedido de adulto feito antes de o aluno ter responsável', function () {
    it('passa a exigir a aprovação de quem virou responsável depois, e avisa essa pessoa', function () {
        $estranho = User::factory()->responsavel()->create(['email' => 'estranho@exemplo.test']);
        $aluno = User::factory()->comIdade(15)->aguardandoResponsavel()->create(['email' => 'menor@exemplo.test']);

        $this->actingAs($estranho)->post('/responsavel/pedidos', ['email' => 'menor@exemplo.test', 'parentesco' => 'outro', 'parentesco_outro' => 'tio', 'declara_responsavel' => true]);
        $pedido = ConviteResponsavel::where('tipo', 'pedido_vinculo')->firstOrFail();
        expect($pedido->exige_aprovacao)->toBeFalse();
        $this->post('/logout');

        // A mãe autoriza pelo convite normal; o aluno fica ativo.
        $mae = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id, 'responsavel_id' => $mae->id]);
        $aluno->forceFill(['status' => 'ativo'])->save();

        $this->actingAs($aluno)->post("/conta/pedidos/{$pedido->id}/aceitar")->assertSessionHasNoErrors();

        expect($aluno->fresh()->responsaveis()->pluck('users.id')->all())->toBe([$mae->id])
            ->and($pedido->fresh()->exige_aprovacao)->toBeTrue()->and($pedido->fresh()->usado_em)->toBeNull();
        Mail::assertSent(PedidoDeVinculoAosResponsaveis::class, fn ($m) => $m->hasTo('mae@exemplo.test'));

        $this->post('/logout');
        $this->actingAs($mae)->post("/responsavel/pedidos/{$pedido->id}/aprovar")->assertSessionHasNoErrors();
        expect($aluno->fresh()->responsaveis()->count())->toBe(2);
    });

    it('quem o aluno contestou não manda pedido novo para ele', function () {
        [$aluno, $emissor] = alunoComEmissorEOutro();
        $this->actingAs($aluno)->post("/conta/responsaveis/{$emissor->id}/contestar");
        $this->post('/logout');

        $this->actingAs($emissor)->post('/responsavel/pedidos', ['email' => $aluno->email, 'parentesco' => 'pai', 'declara_responsavel' => true])
            ->assertSessionHas('status');

        expect(ConviteResponsavel::where('tipo', 'pedido_vinculo')->exists())->toBeFalse();
    });
});

it('aluno que apaga a própria conta deixa o responsável marcado para a limpeza de 30 dias', function () {
    $responsavel = User::factory()->responsavel()->create();
    $aluno = User::factory()->comIdade(15)->comResponsavel($responsavel)->create(['password' => 'minha-senha-123']);

    $this->actingAs($aluno)->delete('/conta', ['senha' => 'minha-senha-123'])->assertRedirect('/');

    expect(User::find($aluno->id))->toBeNull()->and($responsavel->fresh()->sem_aluno_desde)->not->toBeNull();

    $this->travel(31)->days();
    $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();
    expect(User::find($responsavel->id))->toBeNull();
});
