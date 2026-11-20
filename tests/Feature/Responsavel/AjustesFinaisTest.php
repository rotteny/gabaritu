<?php

use App\Domain\Responsavel\LimpezaDePendentes;
use App\Jobs\ProcessarPedidoDeVinculo;
use App\Mail\AcompanhamentoEncerrado;
use App\Mail\ConviteDeResponsavel;
use App\Mail\ConviteDoAlunoAosResponsaveis;
use App\Models\AceiteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Mail::fake());

describe('perfil só com o avatar', function () {
    it('aceita só o avatar, sem apelido nem série', function () {
        $aluno = User::factory()->create(['avatar' => 'polvo', 'apelido' => 'Nando', 'serie' => '2em']);

        $this->actingAs($aluno)->patch('/conta/perfil', ['avatar' => 'baleia'])->assertSessionHasNoErrors();

        $aluno->refresh();
        expect($aluno->avatar)->toBe('baleia')->and($aluno->apelido)->toBe('Nando')->and($aluno->serie)->toBe('2em');
    });

    it('ainda valida apelido e série quando eles vêm', function () {
        $aluno = User::factory()->create();

        $this->actingAs($aluno)->patch('/conta/perfil', ['apelido' => 'A', 'serie' => 'xx'])->assertSessionHasErrors(['apelido', 'serie']);
    });
});

describe('e-mail de recuperação de senha em português', function () {
    it('traduz o assunto para aluno e responsável', function () {
        app()->setLocale('pt_BR');

        foreach ([User::factory()->create(), User::factory()->responsavel()->create()] as $usuario) {
            $mensagem = (new ResetPassword('token'))->toMail($usuario);

            expect($mensagem->subject)->not->toBe('Reset your password')->and($mensagem->subject)->toContain('senha');
        }
    });

    it('envia o e-mail do responsável com assunto em português', function () {
        Notification::fake();
        $responsavel = User::factory()->responsavel()->create();

        $this->post('/responsavel/esqueci-senha', ['email' => $responsavel->email]);

        Notification::assertSentTo($responsavel, ResetPassword::class);
    });
});

describe('convites repetidos para o mesmo e-mail', function () {
    it('reenviar a transferência substitui o convite pendente anterior', function () {
        $responsavel = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($responsavel)->create();
        $dados = ['email' => 'novo@exemplo.test', 'parentesco' => 'avo', 'tipo' => 'transferencia'];

        $this->actingAs($responsavel)->post("/responsavel/alunos/{$aluno->id}/convites", $dados)->assertSessionHasNoErrors();
        $this->actingAs($responsavel)->post("/responsavel/alunos/{$aluno->id}/convites", $dados)->assertSessionHasNoErrors();

        expect(ConviteResponsavel::query()->pendentes()->where('tipo', 'transferencia')->count())->toBe(1)
            ->and(ConviteResponsavel::where('tipo', 'transferencia')->count())->toBe(2);
        Mail::assertSent(ConviteDeResponsavel::class, 2);
    });

    it('e-mails diferentes continuam com convites separados', function () {
        $responsavel = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($responsavel)->create();

        foreach (['a@exemplo.test', 'b@exemplo.test'] as $email) {
            $this->actingAs($responsavel)->post("/responsavel/alunos/{$aluno->id}/convites", ['email' => $email, 'parentesco' => 'avo', 'tipo' => 'transferencia']);
        }

        expect(ConviteResponsavel::query()->pendentes()->where('tipo', 'transferencia')->count())->toBe(2);
    });
});

describe('convite de co-responsável pelo aluno', function () {
    it('avisa todos os responsáveis atuais com nome, parentesco e e-mail mascarado', function () {
        $mae = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        $pai = User::factory()->responsavel()->create(['email' => 'pai@exemplo.test']);
        $aluno = User::factory()->comResponsavel($mae)->comResponsavel($pai, 'pai')->create(['apelido' => 'Nando']);

        $this->actingAs($aluno)->post('/conta/responsaveis', ['email' => 'tio.joao@exemplo.test', 'parentesco' => 'outro', 'parentesco_outro' => 'tio'])
            ->assertSessionHasNoErrors();

        foreach (['mae@exemplo.test', 'pai@exemplo.test'] as $destino) {
            Mail::assertSent(ConviteDoAlunoAosResponsaveis::class, function ($m) use ($destino) {
                $texto = $m->render();

                return $m->hasTo($destino) && str_contains($texto, 'Nando') && str_contains($texto, 'tio')
                    && ! str_contains($texto, 'tio.joao@exemplo.test') && str_contains($texto, 'cancele o convite');
            });
        }
    });

    it('não avisa ninguém quando o aluno não tem responsável ativo', function () {
        $aluno = User::factory()->comIdade(19)->create();

        $this->actingAs($aluno)->post('/conta/responsaveis', ['email' => 'x@exemplo.test', 'parentesco' => 'mae'])->assertSessionHasNoErrors();

        Mail::assertNotSent(ConviteDoAlunoAosResponsaveis::class);
    });

    it('um responsável ativo cancela o convite pendente pelo painel', function () {
        $mae = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($mae)->create();
        $this->actingAs($aluno)->post('/conta/responsaveis', ['email' => 'x@exemplo.test', 'parentesco' => 'mae']);
        $convite = ConviteResponsavel::where('tipo', 'coresponsavel_aluno')->firstOrFail();

        $this->actingAs($mae)->get('/responsavel')->assertInertia(fn ($page) => $page
            ->where('alunos.0.convites_pendentes.0.id', $convite->id)->where('alunos.0.convites_pendentes.0.pode_cancelar', true));
        $this->actingAs($mae)->delete("/responsavel/alunos/{$aluno->id}/convites/{$convite->id}")->assertRedirect();

        expect($convite->fresh()->invalidado_em)->not->toBeNull()->and($convite->fresh()->estaPendente())->toBeFalse();
    });

    it('responsável de outro aluno não cancela (IDOR) e convite de outro tipo não é cancelável', function () {
        $mae = User::factory()->responsavel()->create();
        $aluno = User::factory()->comResponsavel($mae)->create();
        $estranho = User::factory()->responsavel()->create();
        $alheio = User::factory()->comResponsavel($estranho)->create();
        $doAluno = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel_aluno']);
        $doResponsavel = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel', 'solicitante_id' => $mae->id]);

        $this->actingAs($estranho)->delete("/responsavel/alunos/{$aluno->id}/convites/{$doAluno->id}")->assertNotFound();
        $this->actingAs($estranho)->delete("/responsavel/alunos/{$alheio->id}/convites/{$doAluno->id}")->assertNotFound();
        $this->actingAs($mae)->delete("/responsavel/alunos/{$aluno->id}/convites/{$doResponsavel->id}")->assertNotFound();

        expect($doAluno->fresh()->invalidado_em)->toBeNull();
    });
});

describe('aluno de 18 anos ou mais', function () {
    it('encerra todos os vínculos, avisa os responsáveis e invalida convites pendentes', function () {
        $mae = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
        $pai = User::factory()->responsavel()->create(['email' => 'pai@exemplo.test']);
        $aluno = User::factory()->comIdade(19)->comResponsavel($mae)->comResponsavel($pai, 'pai')->create();
        $convite = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => 'coresponsavel_aluno']);

        $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page->where('pode_encerrar_acompanhamento', true));
        $this->actingAs($aluno)->post('/conta/acompanhamento/encerrar')->assertSessionHasNoErrors();

        expect(VinculoResponsavel::where('aluno_id', $aluno->id)->count())->toBe(0)
            ->and($convite->fresh()->estaPendente())->toBeFalse()
            ->and($mae->fresh()->sem_aluno_desde)->not->toBeNull();
        foreach (['mae@exemplo.test', 'pai@exemplo.test'] as $destino) {
            Mail::assertSent(AcompanhamentoEncerrado::class, fn ($m) => $m->hasTo($destino) && str_contains($m->render(), 'não pode ser restaurado'));
        }
        $this->actingAs($aluno)->get('/conta')->assertInertia(fn ($page) => $page->where('pode_encerrar_acompanhamento', false));
    });

    it('o menor de 18 não encerra o acompanhamento', function () {
        $mae = User::factory()->responsavel()->create();
        $aluno = User::factory()->comIdade(16)->comResponsavel($mae)->create();

        $this->actingAs($aluno)->post('/conta/acompanhamento/encerrar')->assertSessionHasErrors('acompanhamento');

        expect($aluno->vinculosComResponsaveis()->count())->toBe(1);
        Mail::assertNotSent(AcompanhamentoEncerrado::class);
    });

    it('a contestação não pode ser restaurada', function () {
        $mae = User::factory()->responsavel()->create();
        $pai = User::factory()->responsavel()->create();
        $aluno = User::factory()->comIdade(18)->comResponsavel($mae)->comResponsavel($pai, 'pai')->create();

        $this->actingAs($aluno)->post("/conta/responsaveis/{$pai->id}/contestar")->assertSessionHasNoErrors();
        $this->actingAs($mae)->post("/responsavel/alunos/{$aluno->id}/suspensos/{$pai->id}/restaurar")->assertSessionHasErrors('vinculo');

        expect(VinculoResponsavel::where('responsavel_id', $pai->id)->firstOrFail()->status)->toBe(VinculoResponsavel::SUSPENSO);
        $this->actingAs($mae)->delete("/responsavel/alunos/{$aluno->id}/suspensos/{$pai->id}")->assertSessionHasNoErrors();
        expect(VinculoResponsavel::where('responsavel_id', $pai->id)->count())->toBe(0);
    });

    it('o menor de 18 continua podendo restaurar', function () {
        $mae = User::factory()->responsavel()->create();
        $pai = User::factory()->responsavel()->create();
        $aluno = User::factory()->comIdade(16)->comResponsavel($mae)->comResponsavel($pai, 'pai')->create();

        $this->actingAs($aluno)->post("/conta/responsaveis/{$pai->id}/contestar");
        $this->actingAs($mae)->post("/responsavel/alunos/{$aluno->id}/suspensos/{$pai->id}/restaurar")->assertSessionHasNoErrors();

        expect(VinculoResponsavel::where('responsavel_id', $pai->id)->firstOrFail()->status)->toBe(VinculoResponsavel::ATIVO);
    });
});

describe('prova do aceite fora do convite', function () {
    it('o convite apagado pela limpeza não leva o registro do aceite', function () {
        $aluno = User::factory()->aguardandoResponsavel()->create();
        $convite = ConviteResponsavel::factory()->comToken('tok-aceite')->create(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->post('/responsavel/convite/tok-aceite', [
            'nome' => 'Maria', 'senha' => 'senha-forte-123', 'senha_confirmation' => 'senha-forte-123', 'ano_nascimento' => now()->year - 40,
            'parentesco' => 'mae', 'declara_responsavel' => true, 'aceite_termos' => true,
        ], ['REMOTE_ADDR' => '203.0.113.9'])->assertSessionHasNoErrors();
        $convite->forceFill(['expira_em' => now()->subDay()])->save();

        $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

        $aceite = AceiteDeResponsavel::firstOrFail();
        expect(ConviteResponsavel::find($convite->id))->toBeNull()
            ->and($aceite->convite_id)->toBe($convite->id)->and($aceite->aluno_id)->toBe($aluno->id)
            ->and($aceite->responsavel_id)->toBe(User::where('email', 'mae@exemplo.test')->value('id'))
            ->and($aceite->termos_versao)->toBe(config('gabaritu.termos_versao'))->and($aceite->aceite_ip)->toBe('203.0.113.9');
    });

    it('o pedido aprovado guarda quem aprovou e quando o aluno aceitou', function () {
        $atual = User::factory()->responsavel()->create(['email' => 'atual@exemplo.test']);
        $aluno = User::factory()->comResponsavel($atual)->create(['email' => 'aluno@exemplo.test']);
        $novo = User::factory()->responsavel()->create();

        $this->actingAs($novo)->post('/responsavel/pedidos', ['email' => 'aluno@exemplo.test', 'parentesco' => 'avo', 'declara_responsavel' => true]);
        $pedido = ConviteResponsavel::where('tipo', 'pedido_vinculo')->firstOrFail();
        $this->actingAs($aluno)->post("/conta/pedidos/{$pedido->id}/aceitar");
        $this->actingAs($atual)->post("/responsavel/pedidos/{$pedido->id}/aprovar");
        $pedido->delete();

        $aceite = AceiteDeResponsavel::firstOrFail();
        expect($aceite->tipo)->toBe('pedido_vinculo')->and($aceite->aprovado_por_id)->toBe($atual->id)->and($aceite->responsavel_id)->toBe($novo->id)
            ->and($aceite->aprovado_em)->not->toBeNull()->and($aceite->aluno_aceitou_em)->not->toBeNull();
    });
});

describe('tempo de resposta do pedido', function () {
    it('responde igual para adulto e para e-mail sem conta, e não cria pedido para o adulto', function () {
        $responsavel = User::factory()->responsavel()->create();
        User::factory()->comIdade(30)->create(['email' => 'adulto@exemplo.test']);
        $respostas = [];

        foreach (['adulto@exemplo.test', 'ninguem@exemplo.test'] as $email) {
            $resposta = $this->actingAs($responsavel)->post('/responsavel/pedidos', ['email' => $email, 'parentesco' => 'avo', 'declara_responsavel' => true]);
            $respostas[] = [$resposta->status(), session('status'), $resposta->headers->get('Location')];
        }

        expect($respostas[0])->toBe($respostas[1])
            ->and(ConviteResponsavel::where('email', 'adulto@exemplo.test')->count())->toBe(0)
            ->and(ConviteResponsavel::where('email', 'ninguem@exemplo.test')->count())->toBe(1);
    });

    it('a busca e o envio saem do caminho da resposta (despachados para depois dela)', function () {
        Bus::fake();
        $responsavel = User::factory()->responsavel()->create();

        $this->actingAs($responsavel)->post('/responsavel/pedidos', ['email' => 'qualquer@exemplo.test', 'parentesco' => 'avo', 'declara_responsavel' => true])
            ->assertSessionHasNoErrors();

        Bus::assertDispatchedAfterResponse(ProcessarPedidoDeVinculo::class);
        Mail::assertNothingSent();
    });
});

describe('limpeza de pendentes', function () {
    it('agenda sem sobreposição', function () {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'gabaritu:limpar-pendentes'));

        expect($evento->withoutOverlapping)->toBeTrue();
    });

    it('não apaga o aluno que foi autorizado depois da seleção, só quem continua pendente', function () {
        $aluno = User::factory()->pendenteHaDias(40)->create(['aviso_remocao_em' => now()->subDays(5)]);
        $limpeza = app(LimpezaDePendentes::class);
        $apagar = new ReflectionMethod($limpeza, 'apagarSeAindaRemovivel');
        $criterio = fn () => User::query()->where('tipo', 'aluno')->where('status', 'aguardando_responsavel');

        $aluno->forceFill(['status' => 'ativo', 'pendente_desde' => null])->save();

        expect($apagar->invoke($limpeza, $aluno->id, $criterio))->toBeFalse()->and(User::find($aluno->id))->not->toBeNull();

        $aluno->forceFill(['status' => 'aguardando_responsavel'])->save();

        expect($apagar->invoke($limpeza, $aluno->id, $criterio))->toBeTrue()->and(User::find($aluno->id))->toBeNull();
    });

    it('responsável que ganhou um aluno depois da seleção não é apagado', function () {
        $responsavel = User::factory()->responsavel()->create(['sem_aluno_desde' => now()->subDays(40)]);
        User::factory()->comResponsavel($responsavel)->create();

        $this->artisan('gabaritu:limpar-pendentes')->assertSuccessful();

        expect(User::find($responsavel->id))->not->toBeNull();
    });
});

describe('modelo de .env de produção', function () {
    it('usa SMTP com placeholders e sem segredos', function () {
        $conteudo = file_get_contents(base_path('infra/producao/env.producao.template'));

        expect($conteudo)->toContain('MAIL_MAILER=smtp')->not->toContain('MAIL_MAILER=log')
            ->and($conteudo)->toContain('MAIL_HOST=@SMTP_HOST@', 'MAIL_USERNAME=@SMTP_USUARIO@', 'MAIL_PASSWORD=@SMTP_SENHA@');
    });
});
