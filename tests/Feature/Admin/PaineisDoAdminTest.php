<?php

use App\Models\AdminAuditoria;
use App\Models\Questao;
use App\Models\Tentativa;
use App\Models\User;

beforeEach(function () {
    config(['gabaritu.cadastro_aberto' => true]);
    [$this->admin, $this->segredo] = adminComDoisFatores();
});

it('mostra o e-mail mascarado na lista e na ficha, nunca inteiro', function () {
    $aluno = User::factory()->comIdade(20)->create(['email' => 'joaozinho@gmail.com', 'apelido' => 'joao']);
    comoAdmin($this->admin);

    $lista = $this->get('/admin/alunos')->assertOk();
    expect($lista->getContent())->not->toContain('joaozinho@gmail.com');
    $lista->assertInertia(fn ($p) => $p->component('admin/Alunos')->where('alunos.dados.0.email_mascarado', 'jo*******@gmail.com')->where('alunos.total', 1));

    $ficha = $this->get("/admin/alunos/{$aluno->id}")->assertOk();
    expect($ficha->getContent())->not->toContain('joaozinho@gmail.com');
    $ficha->assertInertia(fn ($p) => $p->component('admin/Aluno')->where('revelar.email', null)->where('revelar.exige_codigo', false));
});

it('registra na auditoria a abertura de ficha de aluno e de responsável', function () {
    $responsavel = User::factory()->responsavel()->create();
    $aluno = User::factory()->comIdade(15)->comResponsavel($responsavel)->create();
    comoAdmin($this->admin);

    $this->get("/admin/alunos/{$aluno->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->has('responsaveis', 1)->where('responsaveis.0.parentesco', 'mae'));
    $this->get("/admin/responsaveis/{$responsavel->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->where('alunos.0.apelido', $aluno->apelido)->missing('responsavel.apelido'));

    expect(AdminAuditoria::query()->pluck('alvo_tipo', 'acao')->all())->toMatchArray(['abrir_aluno' => 'aluno', 'abrir_responsavel' => 'responsavel']);
});

it('não abre responsável como aluno nem o contrário', function () {
    $responsavel = User::factory()->responsavel()->create();
    comoAdmin($this->admin);

    $this->get("/admin/alunos/{$responsavel->id}")->assertNotFound();
});

it('filtra alunos por busca, status e tem responsável', function () {
    $adulto = User::factory()->comIdade(25)->create(['apelido' => 'Zeca']);
    $menor = User::factory()->comIdade(15)->comResponsavel()->create(['apelido' => 'Bia']);
    $pendente = User::factory()->comIdade(15)->aguardandoResponsavel()->create(['apelido' => 'Caio']);
    comoAdmin($this->admin);

    $apelidos = fn (array $q) => collect($this->get('/admin/alunos?'.http_build_query($q))->inertiaProps('alunos.dados'))->pluck('apelido')->sort()->values()->all();

    expect($apelidos(['busca' => 'zec']))->toBe(['Zeca'])
        ->and($apelidos(['busca' => $pendente->email]))->toContain('Caio')
        ->and($apelidos(['status' => 'aguardando_responsavel']))->toBe(['Caio'])
        ->and($apelidos(['tem_responsavel' => 'sim']))->toBe(['Bia'])
        ->and($apelidos(['tem_responsavel' => 'nao']))->toBe(['Caio', 'Zeca'])
        ->and($apelidos(['busca' => '%']))->toBe([]);
});

it('revelar exige motivo e reconfirmação do TOTP vencida, e gera auditoria', function () {
    $aluno = User::factory()->comIdade(20)->create(['email' => 'secreto@exemplo.test']);
    comoAdmin($this->admin, reconfirmado: false);
    $url = "/admin/alunos/{$aluno->id}/revelar-email";

    $this->post($url, ['codigo' => codigoTotp($this->segredo)])->assertSessionHasErrors('motivo');
    $this->post($url, ['motivo' => 'suporte ao aluno'])->assertSessionHasErrors('codigo');
    $this->post($url, ['motivo' => 'suporte ao aluno', 'codigo' => '000000'])->assertSessionHasErrors('codigo');
    expect(AdminAuditoria::query()->where('acao', 'revelar_email')->count())->toBe(0);

    $this->post($url, ['motivo' => 'suporte ao aluno', 'codigo' => codigoTotp($this->segredo)])->assertRedirect("/admin/alunos/{$aluno->id}");
    $this->get("/admin/alunos/{$aluno->id}")->assertInertia(fn ($p) => $p->where('revelar.email', 'secreto@exemplo.test'));
    $this->get("/admin/alunos/{$aluno->id}")->assertInertia(fn ($p) => $p->where('revelar.email', null));

    $auditoria = AdminAuditoria::query()->where('acao', 'revelar_email')->sole();
    expect($auditoria->motivo)->toBe('suporte ao aluno')->and($auditoria->alvo_id)->toBe($aluno->id)->and($auditoria->alvo_tipo)->toBe('aluno');
});

it('dentro dos 15 minutos não pede o código de novo, mas passados 15 pede', function () {
    $aluno = User::factory()->comIdade(20)->create();
    comoAdmin($this->admin);
    $url = "/admin/alunos/{$aluno->id}/revelar-email";

    $this->post($url, ['motivo' => 'primeiro motivo'])->assertSessionHasNoErrors();
    $this->travel(16)->minutes();
    comoAdmin($this->admin, reconfirmado: false);
    $this->post($url, ['motivo' => 'segundo motivo'])->assertSessionHasErrors('codigo');
});

it('revela e-mail de responsável com auditoria própria', function () {
    $responsavel = User::factory()->responsavel()->create(['email' => 'mae@exemplo.test']);
    comoAdmin($this->admin);

    $this->post("/admin/responsaveis/{$responsavel->id}/revelar-email", ['motivo' => 'contato legal'])->assertRedirect();
    expect(AdminAuditoria::query()->where('acao', 'revelar_email')->sole()->alvo_tipo)->toBe('responsavel');
});

it('troca o status jurídico de questão INEP com auditoria e esconde o remover na hora', function () {
    config(['gabaritu.exibir_inep' => true]);
    $conteudo = conteudoDeTeste(2);
    $inep = $conteudo['questoes'][0];
    $inep->update(['origem' => 'inep', 'revisao_juridica' => 'pendente']);
    comoAdmin($this->admin);
    expect(Questao::query()->servivel()->whereKey($inep->id)->exists())->toBeTrue();

    $this->patch("/admin/questoes/{$inep->id}/status-juridico", ['status' => 'remover'])->assertRedirect("/admin/questoes/{$inep->id}");

    expect(Questao::query()->servivel()->whereKey($inep->id)->exists())->toBeFalse()
        ->and($inep->fresh()->revisao_juridica)->toBe('remover');
    $a = AdminAuditoria::query()->where('acao', 'status_juridico_alterado')->sole();
    expect($a->detalhes)->toBe(['de' => 'pendente', 'para' => 'remover'])->and($a->alvo_tipo)->toBe('questao');

    $this->patch("/admin/questoes/{$inep->id}/status-juridico", ['status' => 'remover']);
    expect(AdminAuditoria::query()->where('acao', 'status_juridico_alterado')->count())->toBe(1);
    $this->patch("/admin/questoes/{$inep->id}/status-juridico", ['status' => 'invalido'])->assertSessionHasErrors('status');
});

it('não altera status jurídico de questão autoral', function () {
    $autoral = conteudoDeTeste(1)['questoes'][0];
    comoAdmin($this->admin);

    $this->patch("/admin/questoes/{$autoral->id}/status-juridico", ['status' => 'remover'])->assertNotFound();
});

it('não deixa editar nem apagar a auditoria', function () {
    $linha = AdminAuditoria::create(['admin_id' => $this->admin->id, 'admin_email' => 'x', 'acao' => 'login']);

    expect(fn () => $linha->update(['acao' => 'outra']))->toThrow(LogicException::class)
        ->and(fn () => $linha->delete())->toThrow(LogicException::class);
});

it('marca questão suspeita (acerto baixo, alto e distrator dominante) e filtra', function () {
    $conteudo = conteudoDeTeste(3);
    [$baixa, $alta, $normal] = $conteudo['questoes'];
    $aluno = User::factory()->comIdade(20)->create();
    // a unicidade (tentativa, questão) exige uma tentativa por resposta
    $inserir = function (Questao $q, int $certas, int $erradas) use ($aluno): void {
        $alternativas = $q->alternativas()->get();
        foreach (range(1, $certas + $erradas) as $i) {
            $t = Tentativa::create(['user_id' => $aluno->id, 'tipo' => 'questionario', 'seed' => $i, 'iniciada_em' => now()]);
            $alt = $i <= $certas ? $alternativas->firstWhere('correta', true) : $alternativas[1];
            DB::table('respostas')->insert([
                'user_id' => $aluno->id, 'tentativa_id' => $t->id, 'questao_id' => $q->id, 'alternativa_id' => $alt->id,
                'contexto' => 'normal', 'correta' => $i <= $certas, 'chute' => false, 'repetida' => false,
                'servida_em' => now(), 'respondida_em' => now(), 'tempo_ms' => 4000,
            ]);
        }
    };
    $inserir($baixa, 2, 20);
    $inserir($alta, 20, 0);
    $inserir($normal, 12, 10);

    comoAdmin($this->admin);
    $resposta = $this->get('/admin/questoes?suspeita=1')->assertOk();
    $codigos = collect($resposta->inertiaProps('questoes.dados'))->pluck('codigo')->sort()->values()->all();

    expect($codigos)->toBe([$baixa->codigo, $alta->codigo])
        ->and($resposta->inertiaProps('resumo.suspeitas'))->toBe(2);
    $linha = collect($resposta->inertiaProps('questoes.dados'))->firstWhere('codigo', $baixa->codigo);
    expect($linha['motivos_suspeita'])->toContain('acerto_baixo')->and($linha['respostas'])->toBe(22)->and($linha['distribuicao'])->toHaveCount(5);
});

it('calcula cartões, séries e funil do dashboard', function () {
    $ativo = User::factory()->comIdade(20)->create();
    User::factory()->comIdade(15)->aguardandoResponsavel()->create();
    User::factory()->responsavel()->create();
    comoAdmin($this->admin);

    $this->get('/admin')->assertOk()->assertInertia(fn ($p) => $p
        ->where('cartoes.alunos.total', 2)->where('cartoes.alunos.por_status.aguardando_responsavel', 1)
        ->where('cartoes.responsaveis', 1)->where('cartoes.cadastros.hoje', 2)
        ->where('funil.etapas.0.valor', 2)->where('funil.etapas.1.valor', 1)->where('funil.menores.cadastrados', 1)
        ->where('periodo', '30')->has('series', 30));
    $this->get('/admin?periodo=tudo')->assertInertia(fn ($p) => $p->where('periodo', 'tudo')->has('series', 1));
    $this->get('/admin?periodo=90')->assertInertia(fn ($p) => $p->has('series', 90));
    expect($ativo)->not->toBeNull();
});

it('abre a página de respostas', function () {
    comoAdmin($this->admin);

    $this->get('/admin/respostas')->assertOk()->assertInertia(fn ($p) => $p
        ->component('admin/Respostas')->has('serie')->where('abandono.taxa_pct', null)->has('materias')->has('etiquetas_de_erro'));
});
