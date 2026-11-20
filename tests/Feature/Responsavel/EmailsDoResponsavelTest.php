<?php

use App\Mail\AlunoAutorizado;
use App\Mail\AutorizacaoConfirmada;
use App\Mail\CadastroConfirmado;
use App\Mail\ConviteDeResponsavel;
use App\Models\ConviteResponsavel;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Illuminate\Support\Facades\Mail;

function emailDeConvite(string $tipo, string $emailDestino = 'dest@exemplo.test'): string
{
    $aluno = User::factory()->aguardandoResponsavel()->create(['apelido' => 'Joaozinho', 'serie' => '1em']);
    $convite = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id, 'tipo' => $tipo, 'email' => $emailDestino]);

    return (new ConviteDeResponsavel($convite, $aluno, 'tok123'))->render();
}

describe('confirmação de cadastro', function () {
    it('adulto: conta pronta com botão Entrar', function () {
        $aluno = User::factory()->create(['apelido' => 'Ana']);

        $html = (new CadastroConfirmado($aluno))->render();

        expect($html)->toContain('Sua conta está pronta', 'Entrar', '/entrar')->not->toContain('autorização');
    });

    it('menor: falta a autorização, com e-mail mascarado, o que vem a seguir e os 7 dias', function () {
        $aluno = User::factory()->aguardandoResponsavel('mae@exemplo.test')->create(['apelido' => 'Ana']);
        $convite = ConviteResponsavel::factory()->create(['aluno_id' => $aluno->id]);

        $html = (new CadastroConfirmado($aluno, $convite))->render();

        expect($html)->toContain('Falta a autorização do seu responsável', 'ma***@exemplo.test', 'O que acontece a seguir', '7 dias', '/aguardando-responsavel')
            ->not->toContain('mae@exemplo.test');
    });
});

describe('convite pedido pelo aluno', function () {
    it('tipos autorizacao e coresponsavel_aluno, sem conta: Criar conta e autorizar', function (string $tipo) {
        $html = emailDeConvite($tipo);

        expect($html)->toContain('Joaozinho pediu que você seja responsável no Gabaritu', 'Criar conta e autorizar', '/responsavel/convite/tok123')
            ->toContain('só nome, ano de nascimento', 'parentesco', 'desempenho', 'teto de zoeira')
            ->toContain('7 dias', 'Se você não conhece esse aluno, ignore este e-mail', '/termos', '/privacidade')
            ->not->toContain('Entrar e autorizar');
    })->with(['autorizacao', 'coresponsavel_aluno']);

    it('com conta de responsável: Entrar e autorizar, via login com redirect interno', function (string $tipo) {
        User::factory()->responsavel()->create(['email' => 'Dest@exemplo.test']);

        $html = emailDeConvite($tipo);

        expect($html)->toContain('Entrar e autorizar', '/entrar?redirect=', urlencode('/responsavel/convite/tok123'), 'ignore este e-mail', '/termos', '/privacidade')
            ->not->toContain('Criar conta e autorizar');
    })->with(['autorizacao', 'coresponsavel_aluno']);

    it('conta de aluno com o mesmo e-mail não conta como conta de responsável', function () {
        User::factory()->create(['email' => 'dest@exemplo.test']);

        expect(emailDeConvite('autorizacao'))->toContain('Criar conta e autorizar');
    });

    it('coresponsavel e transferencia (pedidos por responsável) mantêm o texto próprio', function () {
        expect(emailDeConvite('coresponsavel'))->toContain('convidou você', 'Criar conta e aceitar', '/termos');
        expect(emailDeConvite('transferencia'))->toContain('transferir a responsabilidade', 'Criar conta e aceitar', '/privacidade');
    });

    it('usa os assuntos certos nos envios reais', function () {
        Mail::fake();
        $aluno = User::factory()->comResponsavel()->create(['apelido' => 'Joaozinho']);

        $this->actingAs($aluno)->post('/conta/responsaveis', ['email' => 'tio@exemplo.test', 'parentesco' => 'pai']);

        Mail::assertSent(ConviteDeResponsavel::class, fn (ConviteDeResponsavel $m) => $m->hasSubject('Joaozinho pediu que você seja responsável no Gabaritu'));
    });
});

describe('confirmação de autorização', function () {
    it('para o responsável: resumo, painel, como revogar e o registro do aceite', function () {
        $aluno = User::factory()->create(['apelido' => 'Joaozinho']);
        $responsavel = User::factory()->responsavel()->create(['apelido' => 'Dona Marta']);
        $vinculo = VinculoResponsavel::factory()->create([
            'aluno_id' => $aluno->id, 'responsavel_id' => $responsavel->id,
            'aceito_em' => '2026-11-20 15:30:00', 'termos_versao' => '2026-11-20',
        ]);

        $html = (new AutorizacaoConfirmada($aluno, $responsavel, $vinculo))->render();

        expect($html)->toContain('Você autorizou Joaozinho', 'Dona Marta', 'desempenho', 'teto de zoeira', 'revogar', 'transfira', '/responsavel')
            ->toContain('20/11/2026', '12:30', 'versão 2026-11-20');
    });

    it('é enviada ao responsável (e ao aluno) quando o convite é aceito', function () {
        Mail::fake();
        $aluno = User::factory()->aguardandoResponsavel()->create(['email' => 'aluno@exemplo.test']);
        [, $token] = conviteComToken(['aluno_id' => $aluno->id, 'email' => 'mae@exemplo.test']);

        $this->post("/responsavel/convite/{$token}", dadosDeAceiteNovo())->assertRedirect('/responsavel');

        Mail::assertSent(AutorizacaoConfirmada::class, 1);
        Mail::assertSent(AutorizacaoConfirmada::class, fn ($m) => $m->hasTo('mae@exemplo.test'));
        Mail::assertSent(AlunoAutorizado::class, fn ($m) => $m->hasTo('aluno@exemplo.test'));
    });
});
