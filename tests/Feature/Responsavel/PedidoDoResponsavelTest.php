<?php

use App\Mail\AutorizacaoConfirmada;
use App\Mail\ConviteDeResponsavel;
use App\Mail\PedidoDeResponsavelAoAluno;
use App\Mail\PedidoDeVinculoAosResponsaveis;
use App\Mail\VinculoContestado;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Mail::fake();
    $this->responsavel = User::factory()->responsavel()->create(['apelido' => 'Dona Marta', 'email' => 'marta@exemplo.test', 'password' => 'minha-senha-123']);
});

function pedir(string $email, array $extra = []): TestResponse
{
    return test()->actingAs(test()->responsavel)->post('/responsavel/pedidos', [
        'email' => $email, 'parentesco' => 'avo', 'declara_responsavel' => true, ...$extra,
    ]);
}

describe('A1: e-mail sem conta', function () {
    it('envia o convite ao aluno com link de cadastro e responde de forma genérica', function () {
        pedir('Novo@exemplo.test')->assertSessionHas('status', 'Se existir um aluno com esse e-mail, ele receberá o pedido.')->assertSessionHasNoErrors();

        $convite = ConviteResponsavel::firstOrFail();
        expect($convite->tipo)->toBe('pedido_responsavel')->and($convite->aluno_id)->toBeNull()->and($convite->email)->toBe('novo@exemplo.test')
            ->and($convite->solicitante_id)->toBe($this->responsavel->id)->and($convite->aceite_em)->not->toBeNull();
        Mail::assertSent(PedidoDeResponsavelAoAluno::class, fn ($m) => $m->hasTo('novo@exemplo.test')
            && $m->hasSubject('Dona Marta quer ser seu responsável no Gabaritu') && str_contains($m->render(), '/cadastro?convite='.$m->token));
    });

    it('o cadastro pelo link já sai vinculado e ativo, sem enviar autorização', function () {
        pedir('menor@exemplo.test');
        $token = null;
        Mail::assertSent(PedidoDeResponsavelAoAluno::class, function ($m) use (&$token) {
            $token = $m->token;

            return true;
        });
        $this->post('/logout');

        $this->get("/cadastro?convite={$token}")->assertInertia(fn ($page) => $page
            ->where('convite_responsavel.responsavel.nome', 'Dona Marta')->where('convite_responsavel.parentesco', 'avo')->where('convite_responsavel.email', 'menor@exemplo.test'));

        $this->post('/cadastro', dadosDeCadastro([
            'email' => 'menor@exemplo.test', 'ano_nascimento' => (int) now()->format('Y') - 15,
            'convite' => $token, 'confirma_responsavel' => true,
        ]))->assertRedirect('/inicio')->assertSessionHasNoErrors();

        $aluno = User::firstWhere('email', 'menor@exemplo.test');
        $vinculo = VinculoResponsavel::firstOrFail();
        expect($aluno->status)->toBe('ativo')->and($vinculo->responsavel_id)->toBe($this->responsavel->id)->and($vinculo->parentesco)->toBe('avo')
            ->and($vinculo->aceite_ip)->not->toBeNull()->and($vinculo->declara_responsavel)->toBeTrue();
        expect(ConviteResponsavel::firstOrFail()->usado_em)->not->toBeNull();
        Mail::assertNotSent(ConviteDeResponsavel::class);
        Mail::assertSent(AutorizacaoConfirmada::class, fn ($m) => $m->hasTo('marta@exemplo.test'));
    });

    it('maior de 18 anos: o convite não vale', function () {
        $token = tokenDoPedido('adulto@exemplo.test');

        $this->post('/cadastro', dadosDeCadastro(['email' => 'adulto@exemplo.test', 'convite' => $token, 'confirma_responsavel' => true]))
            ->assertSessionHasErrors(['convite' => 'Maiores de idade não precisam de responsável.']);
        expect(User::where('email', 'adulto@exemplo.test')->count())->toBe(0);
    });

    it('se o aluno não confirmar, segue o fluxo normal e pede o e-mail de outro responsável', function () {
        $token = tokenDoPedido('menor@exemplo.test');
        $menor = ['email' => 'menor@exemplo.test', 'ano_nascimento' => (int) now()->format('Y') - 15, 'convite' => $token, 'confirma_responsavel' => false];

        $this->post('/cadastro', dadosDeCadastro($menor))->assertSessionHasErrors('email_responsavel');
        $this->post('/cadastro', dadosDeCadastro([...$menor, 'email_responsavel' => 'outra@exemplo.test', 'parentesco_responsavel' => 'mae']))
            ->assertRedirect('/aguardando-responsavel');

        expect(VinculoResponsavel::count())->toBe(0)->and(User::firstWhere('email', 'menor@exemplo.test')->status)->toBe('aguardando_responsavel');
        expect(ConviteResponsavel::where('tipo', 'pedido_responsavel')->first()->recusado_em)->not->toBeNull();
    });

    it('o link só vale para o e-mail convidado e não é reaproveitável', function () {
        $token = tokenDoPedido('menor@exemplo.test');
        $dados = ['ano_nascimento' => (int) now()->format('Y') - 15, 'convite' => $token, 'confirma_responsavel' => true];

        $this->post('/cadastro', dadosDeCadastro(['email' => 'outro@exemplo.test', ...$dados]))->assertSessionHasErrors('convite');
        $this->post('/cadastro', dadosDeCadastro(['email' => 'menor@exemplo.test', ...$dados]))->assertRedirect('/inicio');
        $this->post('/logout');
        $this->get("/cadastro?convite={$token}")->assertInertia(fn ($page) => $page->where('convite_responsavel', null));
    });
});

function tokenDoPedido(string $email): string
{
    pedir($email);
    $token = null;
    Mail::assertSent(PedidoDeResponsavelAoAluno::class, function ($m) use (&$token, $email) {
        if ($m->hasTo($email)) {
            $token = $m->token;
        }

        return true;
    });
    test()->post('/logout');

    return $token;
}

describe('A2/A3: aluno já cadastrado', function () {
    it('sem responsável ativo, basta o aluno aceitar (e ele sai da espera)', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create(['email' => 'menor@exemplo.test', 'password' => 'minha-senha-123']);

        pedir('MENOR@exemplo.test')->assertSessionHas('status');
        $convite = ConviteResponsavel::firstOrFail();
        expect($convite->tipo)->toBe('pedido_vinculo')->and($convite->aluno_id)->toBe($aluno->id)->and($convite->exige_aprovacao)->toBeFalse();
        Mail::assertSent(PedidoDeResponsavelAoAluno::class, fn ($m) => $m->hasTo('menor@exemplo.test') && str_contains($m->render(), 'basta você aceitar'));
        Mail::assertNotSent(PedidoDeVinculoAosResponsaveis::class);

        $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page
            ->where('pedidos_vinculo.0.id', $convite->id)->where('pedidos_vinculo.0.solicitante.nome', 'Dona Marta')
            ->where('pedidos_vinculo.0.solicitante.parentesco', 'avo')->where('pedidos_vinculo.0.solicitante.email_mascarado', 'ma***@exemplo.test')
            ->where('pedidos_vinculo.0.aguarda_responsavel', false));
        $this->post("/conta/pedidos/{$convite->id}/aceitar")->assertSessionHasNoErrors();

        expect($aluno->fresh()->status)->toBe('ativo')->and($aluno->responsaveis()->pluck('users.id')->all())->toBe([$this->responsavel->id]);
        Mail::assertSent(AutorizacaoConfirmada::class, fn ($m) => $m->hasTo('marta@exemplo.test'));
    });

    it('com responsável ativo, exige o aceite do aluno E a aprovação de um responsável atual (em qualquer ordem)', function () {
        $atual = User::factory()->responsavel()->create(['email' => 'atual@exemplo.test', 'apelido' => 'Pai Atual']);
        $aluno = User::factory()->comResponsavel($atual)->create(['email' => 'menor@exemplo.test']);

        pedir('menor@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();
        expect($convite->exige_aprovacao)->toBeTrue();
        Mail::assertSent(PedidoDeVinculoAosResponsaveis::class, fn ($m) => $m->hasTo('atual@exemplo.test')
            && str_contains($m->render(), 'Dona Marta') && str_contains($m->render(), 'avo') && str_contains($m->render(), 'ma***@exemplo.test') && ! str_contains($m->render(), 'marta@exemplo.test'));

        // só o aluno aceita: ainda não vira vínculo
        $this->actingAs($aluno)->post("/conta/pedidos/{$convite->id}/aceitar");
        expect($aluno->responsaveis()->count())->toBe(1);
        $this->actingAs($atual)->get('/responsavel')->assertInertia(fn ($page) => $page
            ->where('alunos.0.pedidos_pendentes.0.id', $convite->id)->where('alunos.0.pedidos_pendentes.0.aluno_aceitou', true)
            ->where('alunos.0.pedidos_pendentes.0.solicitante.nome', 'Dona Marta'));

        $this->post("/responsavel/pedidos/{$convite->id}/aprovar")->assertSessionHasNoErrors();
        expect($aluno->responsaveis()->count())->toBe(2)->and($convite->fresh()->aprovado_por_id)->toBe($atual->id);
    });

    it('só a aprovação do responsável, sem o aluno, também não vincula', function () {
        $atual = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($atual)->create(['email' => 'menor@exemplo.test']);
        pedir('menor@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();

        $this->actingAs($atual)->post("/responsavel/pedidos/{$convite->id}/aprovar");

        expect($aluno->responsaveis()->count())->toBe(1);
        $this->actingAs($aluno)->post("/conta/pedidos/{$convite->id}/aceitar");
        expect($aluno->responsaveis()->count())->toBe(2);
    });

    it('recusa do aluno ou do responsável encerra o pedido', function (string $quem) {
        $atual = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($atual)->create(['email' => 'menor@exemplo.test']);
        pedir('menor@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();

        $quem === 'aluno'
            ? $this->actingAs($aluno)->post("/conta/pedidos/{$convite->id}/recusar")
            : $this->actingAs($atual)->post("/responsavel/pedidos/{$convite->id}/recusar");

        expect($convite->fresh()->recusado_em)->not->toBeNull();
        $this->actingAs($aluno)->post("/conta/pedidos/{$convite->id}/aceitar")->assertNotFound();
        expect($aluno->responsaveis()->count())->toBe(1);
    })->with(['aluno', 'responsavel']);

    it('o solicitante cancela o próprio pedido', function () {
        pedir('novo@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();

        $this->delete("/responsavel/pedidos/{$convite->id}")->assertRedirect();

        expect($convite->fresh()->estaPendente())->toBeFalse();
    });
});

describe('proteção contra adulto estranho e sem revelar contas', function () {
    it('a resposta é igual para e-mail sem conta, de aluno, de outro responsável e do próprio', function () {
        User::factory()->create(['email' => 'aluno@exemplo.test']);
        User::factory()->responsavel()->create(['email' => 'outro@exemplo.test']);

        $status = collect(['livre@exemplo.test', 'aluno@exemplo.test', 'outro@exemplo.test', 'marta@exemplo.test'])->map(function ($email) {
            pedir($email);

            return session('status');
        })->unique();

        expect($status->all())->toBe(['Se existir um aluno com esse e-mail, ele receberá o pedido.']);
    });

    it('nunca cria pedido para outro responsável, para o próprio e-mail ou para adulto', function () {
        User::factory()->responsavel()->create(['email' => 'outro@exemplo.test']);
        User::factory()->comIdade(30)->create(['email' => 'adulto@exemplo.test']);

        foreach (['outro@exemplo.test', 'marta@exemplo.test', 'adulto@exemplo.test'] as $email) {
            pedir($email);
        }

        expect(ConviteResponsavel::count())->toBe(0);
        Mail::assertNothingSent();
    });

    it('limita a 5 pedidos por dia por responsável, e libera no dia seguinte', function () {
        foreach (range(1, 5) as $i) {
            pedir("aluno{$i}@exemplo.test")->assertSessionHasNoErrors();
        }

        pedir('aluno6@exemplo.test')->assertSessionHasErrors('email');
        expect(ConviteResponsavel::count())->toBe(5);
        $this->travel(25)->hours();
        pedir('aluno6@exemplo.test')->assertSessionHasNoErrors();
    });

    it('exige a declaração de responsável legal e e-mail válido', function () {
        pedir('x@exemplo.test', ['declara_responsavel' => false])->assertSessionHasErrors('declara_responsavel');
        pedir('invalido')->assertSessionHasErrors('email');
        expect(ConviteResponsavel::count())->toBe(0);
    });

    it('não duplica pedido pendente para o mesmo e-mail', function () {
        pedir('novo@exemplo.test');
        pedir('novo@exemplo.test');

        expect(ConviteResponsavel::count())->toBe(1);
    });

    it('IDOR: outro responsável não aprova, recusa nem cancela o pedido alheio; o aluno alheio não aceita', function () {
        $atual = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($atual)->create(['email' => 'menor@exemplo.test']);
        pedir('menor@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();
        $intruso = User::factory()->responsavel()->create();
        $outroAluno = User::factory()->comResponsavel()->create();

        $this->actingAs($intruso)->post("/responsavel/pedidos/{$convite->id}/aprovar")->assertNotFound();
        $this->post("/responsavel/pedidos/{$convite->id}/recusar")->assertNotFound();
        $this->delete("/responsavel/pedidos/{$convite->id}")->assertNotFound();
        $this->actingAs($outroAluno)->post("/conta/pedidos/{$convite->id}/aceitar")->assertNotFound();
        $this->post("/conta/pedidos/{$convite->id}/recusar")->assertNotFound();

        $convite->refresh();
        expect($convite->aprovado_em)->toBeNull()->and($convite->recusado_em)->toBeNull()->and($convite->aluno_aceitou_em)->toBeNull()->and($convite->estaPendente())->toBeTrue();
    });

    it('o pedido do responsável não funciona como convite de responsável', function () {
        $token = tokenDoPedido('novo@exemplo.test');

        $this->get("/responsavel/convite/{$token}")->assertInertia(fn ($page) => $page->where('convite', null));
        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertInertia(fn ($page) => $page->where('convite', null));
    });

    it('o pedido vale 7 dias', function () {
        pedir('novo@exemplo.test');
        $convite = ConviteResponsavel::firstOrFail();

        expect($convite->expira_em->isSameDay(now()->addDays(7)))->toBeTrue();
        $this->travel(8)->days();
        expect($convite->fresh()->estaPendente())->toBeFalse();
    });
});

describe('B: "Essa pessoa não é meu responsável"', function () {
    function alunoComDoisResponsaveis(): array
    {
        $a = User::factory()->responsavel()->create(['apelido' => 'Ana', 'email' => 'ana@exemplo.test']);
        $b = User::factory()->responsavel()->create(['apelido' => 'Beto', 'email' => 'beto@exemplo.test']);
        $aluno = User::factory()->comResponsavel($a)->create(['apelido' => 'Joaozinho']);
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id, 'responsavel_id' => $b->id, 'parentesco' => 'tutor']);

        return [$aluno, $a, $b];
    }

    it('suspende na hora o acesso, avisa os outros responsáveis e o contato do Gabaritu', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();

        $this->actingAs($aluno)->post("/conta/responsaveis/{$beto->id}/contestar")->assertSessionHasNoErrors();

        expect($aluno->vinculosComResponsaveis()->where('responsavel_id', $beto->id)->first()->status)->toBe('suspenso')
            ->and($aluno->fresh()->status)->toBe('ativo')->and($beto->fresh()->sem_aluno_desde)->not->toBeNull();
        Mail::assertSent(VinculoContestado::class, 2);
        Mail::assertSent(VinculoContestado::class, fn ($m) => $m->hasTo('ana@exemplo.test') && str_contains($m->render(), 'Beto') && str_contains($m->render(), 'be***@exemplo.test'));
        Mail::assertSent(VinculoContestado::class, fn ($m) => $m->hasTo(config('gabaritu.email_contato')));
        Mail::assertNotSent(VinculoContestado::class, fn ($m) => $m->hasTo('beto@exemplo.test'));

        $this->actingAs($beto)->get("/responsavel/alunos/{$aluno->id}")->assertNotFound();
        $this->get('/responsavel')->assertInertia(fn ($page) => $page->where('alunos', []));
        $this->actingAs($beto)->patch("/responsavel/alunos/{$aluno->id}/zoeira", ['teto' => 'fofo'])->assertNotFound();
    });

    it('quem sobrou vê o vínculo suspenso e pode restaurar', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();
        $this->actingAs($aluno)->post("/conta/responsaveis/{$beto->id}/contestar");

        $this->actingAs($ana)->get("/responsavel/alunos/{$aluno->id}")->assertInertia(fn ($page) => $page
            ->where('aluno.responsaveis.1.nome', 'Beto')->where('aluno.responsaveis.1.status', 'suspenso'));
        $this->post("/responsavel/alunos/{$aluno->id}/suspensos/{$beto->id}/restaurar")->assertSessionHasNoErrors();

        expect($aluno->responsaveis()->count())->toBe(2)->and($beto->fresh()->sem_aluno_desde)->toBeNull();
        $this->actingAs($beto)->get("/responsavel/alunos/{$aluno->id}")->assertOk();
    });

    it('quem sobrou pode confirmar a remoção', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();
        $this->actingAs($aluno)->post("/conta/responsaveis/{$beto->id}/contestar");

        $this->actingAs($ana)->delete("/responsavel/alunos/{$aluno->id}/suspensos/{$beto->id}")->assertSessionHasNoErrors();

        expect($aluno->vinculosComResponsaveis()->count())->toBe(1);
    });

    it('se não sobrar responsável ativo, o aluno volta para aguardando e pode convidar outro', function () {
        $unico = User::factory()->responsavel()->create(['email' => 'unico@exemplo.test']);
        $aluno = User::factory()->comResponsavel($unico)->create(['email' => 'aluno@exemplo.test']);

        $this->actingAs($aluno)->post("/conta/responsaveis/{$unico->id}/contestar");

        $aluno->refresh();
        expect($aluno->status)->toBe('aguardando_responsavel')->and($aluno->pendente_desde)->not->toBeNull();
        Mail::assertSent(VinculoContestado::class, 1);
        $this->get('/inicio')->assertRedirect('/aguardando-responsavel');
        $this->patch('/aguardando-responsavel/email', ['email_responsavel' => 'novo@exemplo.test', 'parentesco_responsavel' => 'mae'])->assertSessionHasNoErrors();
        expect(ConviteResponsavel::where('tipo', 'autorizacao')->count())->toBe(1);
    });

    it('IDOR: não contesta quem não é responsável do próprio aluno, nem o aluno alheio contesta', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();
        $intruso = User::factory()->responsavel()->create();
        $outroAluno = User::factory()->comResponsavel()->create();

        $this->actingAs($outroAluno)->post("/conta/responsaveis/{$ana->id}/contestar")->assertNotFound();
        $this->actingAs($aluno)->post("/conta/responsaveis/{$intruso->id}/contestar")->assertNotFound();
        $this->actingAs($intruso)->post("/conta/responsaveis/{$ana->id}/contestar")->assertNotFound();

        expect($aluno->vinculosComResponsaveis()->where('status', 'suspenso')->count())->toBe(0);
        Mail::assertNothingSent();
    });

    it('IDOR: um responsável suspenso ou alheio não restaura nem remove', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();
        $this->actingAs($aluno)->post("/conta/responsaveis/{$beto->id}/contestar");
        $intruso = User::factory()->responsavel()->create();

        $this->actingAs($beto)->post("/responsavel/alunos/{$aluno->id}/suspensos/{$beto->id}/restaurar")->assertNotFound();
        $this->actingAs($intruso)->post("/responsavel/alunos/{$aluno->id}/suspensos/{$beto->id}/restaurar")->assertNotFound();
        $this->actingAs($intruso)->delete("/responsavel/alunos/{$aluno->id}/suspensos/{$beto->id}")->assertNotFound();
        $this->actingAs($ana)->post("/responsavel/alunos/{$aluno->id}/suspensos/{$ana->id}/restaurar")->assertNotFound();

        expect($aluno->responsaveis()->count())->toBe(1);
    });

    it('a Conta do aluno traz id, parentesco e status de cada responsável', function () {
        [$aluno, $ana, $beto] = alunoComDoisResponsaveis();

        $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page
            ->where('responsaveis.1.id', $beto->id)->where('responsaveis.1.parentesco', 'tutor')->where('responsaveis.1.status', 'ativo'));
    });
});

describe('C: ver os outros responsáveis', function () {
    it('o painel e a página do aluno trazem nome, parentesco, e-mail mascarado e data de autorização', function () {
        $a = User::factory()->responsavel()->create(['apelido' => 'Ana', 'email' => 'ana@exemplo.test']);
        $b = User::factory()->responsavel()->create(['apelido' => 'Beto', 'email' => 'beto@exemplo.test']);
        $aluno = User::factory()->comResponsavel($a)->create();
        VinculoResponsavel::factory()->create(['aluno_id' => $aluno->id, 'responsavel_id' => $b->id, 'parentesco' => 'pai', 'aceito_em' => '2026-11-10 12:00:00']);

        foreach (['/responsavel' => 'alunos.0', "/responsavel/alunos/{$aluno->id}" => 'aluno'] as $rota => $chave) {
            $this->actingAs($a)->get($rota)->assertInertia(fn ($page) => $page
                ->where("{$chave}.responsaveis.0.sou_eu", true)
                ->where("{$chave}.responsaveis.1.nome", 'Beto')->where("{$chave}.responsaveis.1.parentesco", 'pai')
                ->where("{$chave}.responsaveis.1.email_mascarado", 'be***@exemplo.test')->where("{$chave}.responsaveis.1.sou_eu", false)
                ->where("{$chave}.responsaveis.1.autorizado_em", fn ($v) => str_starts_with($v, '2026-11-10')));
        }
    });
});
