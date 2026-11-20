<?php

use App\Domain\Admin\ExportadorDeCsv;
use App\Http\Controllers\Admin\RevelarEmailController;
use App\Models\Admin;
use App\Models\AdminAuditoria;
use App\Models\Questao;
use App\Models\Tentativa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['gabaritu.cadastro_aberto' => true]);
    [$this->a, $this->segredoA] = adminComDoisFatores('a@gabaritu.test');
    [$this->b] = adminComDoisFatores('b@gabaritu.test');
});

it('não deixa reaproveitar a sessão do admin A para pular o 2FA do admin B', function () {
    $this->post('/admin/entrar', ['email' => 'a@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['codigo' => codigoTotp($this->segredoA)])->assertRedirect('/admin');
    $this->get('/admin')->assertOk();

    $this->post('/admin/entrar', ['email' => 'b@gabaritu.test', 'senha' => 'Senha-do-admin-123'])->assertRedirect('/admin/dois-fatores');

    $this->get('/admin')->assertRedirect('/admin/dois-fatores');
    $this->get('/admin/alunos')->assertRedirect('/admin/dois-fatores');
});

it('não grava a senha digitada no login que falhou', function () {
    $this->post('/admin/entrar', ['email' => 'a@gabaritu.test', 'senha' => 'senha-errada-visivel'])->assertSessionHasErrors('email');

    expect(json_encode(session()->all()))->not->toContain('senha-errada-visivel');
    $this->post('/admin/dois-fatores/desafio', ['codigo' => '654321', 'recuperacao' => 'abcdef-ghjkmn']);
    expect(json_encode(session()->all()))->not->toContain('654321')->not->toContain('abcdef-ghjkmn');
});

it('derruba as sessões abertas quando o 2FA é reiniciado pelo comando', function () {
    $this->post('/admin/entrar', ['email' => 'a@gabaritu.test', 'senha' => 'Senha-do-admin-123']);
    $this->post('/admin/dois-fatores/desafio', ['codigo' => codigoTotp($this->segredoA)]);
    $this->get('/admin')->assertOk();

    $this->artisan('gabaritu:admin', ['acao' => '2fa-reset', 'email' => 'a@gabaritu.test'])->assertSuccessful();

    app('auth')->forgetGuards(); // cada requisição real recarrega o admin do banco
    $this->get('/admin')->assertRedirect('/admin/entrar');
});

it('busca por e-mail só aceita o e-mail inteiro, para não reconstruir endereço sem revelar', function () {
    User::factory()->comIdade(20)->create(['email' => 'joaozinho@gmail.com', 'apelido' => 'zeca']);
    User::factory()->responsavel()->create(['email' => 'mariana@gmail.com']);
    comoAdmin($this->a);

    $alunos = fn (string $q) => count($this->get('/admin/alunos?busca='.urlencode($q))->inertiaProps('alunos.dados'));
    $responsaveis = fn (string $q) => count($this->get('/admin/responsaveis?busca='.urlencode($q))->inertiaProps('responsaveis.dados'));

    expect($alunos('joa'))->toBe(0)->and($alunos('joaozinho@'))->toBe(0)->and($alunos('JOAOZINHO@gmail.com'))->toBe(1)->and($alunos('zec'))->toBe(1)
        ->and($responsaveis('mar'))->toBe(0)->and($responsaveis('mariana@gmail'))->toBe(0)->and($responsaveis('Mariana@Gmail.com'))->toBe(1);
});

it('a auditoria não pode ser alterada nem apagada nem pelo banco', function () {
    $linha = AdminAuditoria::create(['admin_id' => $this->a->id, 'admin_email' => 'a@gabaritu.test', 'acao' => 'login']);

    expect(fn () => DB::table('admin_auditoria')->where('id', $linha->id)->update(['acao' => 'outra']))->toThrow(QueryException::class);
});

it('a auditoria não pode ser apagada pelo banco', function () {
    $linha = AdminAuditoria::create(['admin_id' => $this->a->id, 'admin_email' => 'a@gabaritu.test', 'acao' => 'login']);

    expect(fn () => DB::table('admin_auditoria')->where('id', $linha->id)->delete())->toThrow(QueryException::class);
});

it('apagar o admin continua possível e mantém a linha de auditoria sem o vínculo', function () {
    $linha = AdminAuditoria::create(['admin_id' => $this->b->id, 'admin_email' => 'b@gabaritu.test', 'acao' => 'login']);

    $this->b->delete();

    $fresh = DB::table('admin_auditoria')->find($linha->id);
    expect($fresh->admin_id)->toBeNull()->and($fresh->admin_email)->toBe('b@gabaritu.test');
});

it('neutraliza também células iniciadas por tab ou retorno de carro no CSV', function () {
    expect(ExportadorDeCsv::proteger("\t=1+1"))->toBe("'\t=1+1")->and(ExportadorDeCsv::proteger("\rx"))->toBe("'\rx");
});

it('só mostra o e-mail revelado na ficha do alvo certo', function () {
    $alvo = User::factory()->comIdade(20)->create(['email' => 'alvo@exemplo.test']);
    $outro = User::factory()->comIdade(20)->create(['email' => 'outro@exemplo.test']);
    comoAdmin($this->a);

    $this->post("/admin/alunos/{$alvo->id}/revelar-email", ['motivo' => 'suporte ao aluno'])->assertRedirect();
    $this->get("/admin/alunos/{$outro->id}")->assertInertia(fn ($p) => $p->where('revelar.email', null));

    $this->withSession([RevelarEmailController::FLASH_EMAIL => ['id' => $alvo->id, 'tipo' => 'aluno', 'email' => 'alvo@exemplo.test']])
        ->get("/admin/alunos/{$outro->id}")->assertInertia(fn ($p) => $p->where('revelar.email', null));
    $this->withSession([RevelarEmailController::FLASH_EMAIL => ['id' => $alvo->id, 'tipo' => 'aluno', 'email' => 'alvo@exemplo.test']])
        ->get("/admin/alunos/{$alvo->id}")->assertInertia(fn ($p) => $p->where('revelar.email', 'alvo@exemplo.test'));
});

it('o limite de reports por dia é checado dentro de transação com lock', function () {
    $questoes = conteudoDeTeste(21)['questoes'];
    $aluno = User::factory()->comIdade(20)->create();
    foreach ($questoes as $q) {
        $t = Tentativa::create(['user_id' => $aluno->id, 'tipo' => 'questionario', 'seed' => 1, 'iniciada_em' => now()]);
        DB::table('respostas')->insert(['user_id' => $aluno->id, 'tentativa_id' => $t->id, 'questao_id' => $q->id, 'alternativa_id' => $q->alternativas()->first()->id,
            'contexto' => 'normal', 'correta' => true, 'chute' => false, 'repetida' => false, 'servida_em' => now(), 'respondida_em' => now(), 'tempo_ms' => 4000]);
    }
    $consultas = [];
    DB::listen(function ($q) use (&$consultas) {
        $consultas[] = strtolower($q->sql);
    });

    $this->actingAs($aluno)->postJson("/questoes/{$questoes[0]->id}/reports", ['motivo' => 'outro'])->assertCreated();

    expect(collect($consultas)->contains(fn ($s) => str_contains($s, 'for update')))->toBeTrue();
    expect(Questao::count())->toBe(21);
});
