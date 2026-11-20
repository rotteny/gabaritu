<?php

use App\Domain\Admin\ExportadorDeCsv;
use App\Domain\Admin\SaudeDoSistema;
use App\Models\AdminAuditoria;
use App\Models\ChaveFala;
use App\Models\ConviteResponsavel;
use App\Models\Questao;
use App\Models\ReportQuestao;
use App\Models\Tentativa;
use App\Models\User;
use App\Models\VinculoResponsavel;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function respostaDireta(User $aluno, Questao $questao, ?CarbonInterface $quando = null, bool $correta = true, ?int $falaId = null): void
{
    $tentativa = Tentativa::create(['user_id' => $aluno->id, 'tipo' => 'questionario', 'seed' => 1, 'iniciada_em' => now()]);
    $alternativa = $questao->alternativas()->where('correta', $correta)->first();
    DB::table('respostas')->insert([
        'user_id' => $aluno->id, 'tentativa_id' => $tentativa->id, 'questao_id' => $questao->id, 'alternativa_id' => $alternativa->id,
        'contexto' => 'normal', 'correta' => $correta, 'chute' => false, 'repetida' => false, 'variacao_fala_id' => $falaId,
        'servida_em' => $quando?->setTimezone('UTC') ?? now(), 'respondida_em' => $quando?->setTimezone('UTC') ?? now(), 'tempo_ms' => 5000,
    ]);
}

beforeEach(function () {
    config(['gabaritu.cadastro_aberto' => true]);
    [$this->admin] = adminComDoisFatores();
});

describe('report do aluno', function () {
    it('só aceita report de questão já respondida, 1 por questão, e devolve a URL no JSON da resposta', function () {
        $conteudo = conteudoDeTeste(3);
        $aluno = User::factory()->comIdade(20)->create();
        $questao = $conteudo['questoes'][0];
        $url = "/questoes/{$questao->id}/reports";

        $this->actingAs($aluno)->postJson($url, ['motivo' => 'gabarito'])->assertForbidden();

        $tentativa = iniciarTentativa($aluno, $conteudo['questionario']);
        $resposta = responderAtual($aluno, $tentativa, true)->assertOk();
        $resposta->assertJsonPath('report.motivos.0.valor', 'enunciado')->assertJsonStructure(['report' => ['url', 'motivos', 'max_comentario']]);
        $respondida = $resposta->json('report.url');

        $this->actingAs($aluno)->postJson($respondida, ['motivo' => 'gabarito', 'comentario' => ' acho que é a B '])->assertCreated();
        $this->actingAs($aluno)->postJson($respondida, ['motivo' => 'outro'])->assertUnprocessable();
        expect(ReportQuestao::sole()->comentario)->toBe('acho que é a B')->and(ReportQuestao::sole()->status)->toBe('aberto');
    });

    it('valida motivo e tamanho do comentário', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];
        $aluno = User::factory()->comIdade(20)->create();
        respostaDireta($aluno, $questao);

        $this->actingAs($aluno)->postJson("/questoes/{$questao->id}/reports", ['motivo' => 'xis'])->assertUnprocessable();
        $this->actingAs($aluno)->postJson("/questoes/{$questao->id}/reports", ['motivo' => 'outro', 'comentario' => str_repeat('a', 301)])->assertUnprocessable();
        $this->actingAs($aluno)->postJson("/questoes/{$questao->id}/reports", ['motivo' => 'outro', 'comentario' => str_repeat('a', 300)])->assertCreated();
    });

    it('limita a 20 reports por dia', function () {
        $questoes = conteudoDeTeste(22)['questoes'];
        $aluno = User::factory()->comIdade(20)->create();
        foreach ($questoes as $q) {
            respostaDireta($aluno, $q);
        }

        foreach ($questoes->take(20) as $q) {
            $this->actingAs($aluno)->postJson("/questoes/{$q->id}/reports", ['motivo' => 'outro'])->assertCreated();
        }
        $this->actingAs($aluno)->postJson("/questoes/{$questoes[20]->id}/reports", ['motivo' => 'outro'])->assertStatus(429);

        $this->travel(1)->day();
        $this->actingAs($aluno)->postJson("/questoes/{$questoes[20]->id}/reports", ['motivo' => 'outro'])->assertCreated();
    });

    it('não deixa visitante nem responsável reportar', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];

        $this->postJson("/questoes/{$questao->id}/reports", ['motivo' => 'outro'])->assertUnauthorized();
        $this->actingAs(User::factory()->responsavel()->create())->postJson("/questoes/{$questao->id}/reports", ['motivo' => 'outro'])->assertNotFound();
    });
});

describe('fila de reports no admin', function () {
    it('agrupa por questão, mostra só o apelido e conta por motivo', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];
        $a = User::factory()->comIdade(20)->create(['apelido' => 'ana', 'email' => 'ana@exemplo.test']);
        $b = User::factory()->comIdade(20)->create(['apelido' => 'beto']);
        foreach ([[$a, 'gabarito'], [$b, 'imagem']] as [$u, $motivo]) {
            ReportQuestao::create(['questao_id' => $questao->id, 'user_id' => $u->id, 'motivo' => $motivo, 'comentario' => 'oi', 'status' => 'aberto']);
        }
        comoAdmin($this->admin);

        $resposta = $this->get('/admin/reports')->assertOk();
        expect($resposta->getContent())->not->toContain('ana@exemplo.test');
        $resposta->assertInertia(fn ($p) => $p->component('admin/Reports')
            ->where('contagens.aberto', 2)->where('fila.dados.0.total', 2)
            ->where('fila.dados.0.por_motivo.gabarito', 1)->where('fila.dados.0.reports.0.apelido', 'ana'));
    });

    it('muda o status com auditoria, em lote por questão, e reports contam como suspeita', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];
        $alunos = User::factory()->comIdade(20)->count(3)->create();
        $reports = $alunos->map(fn ($u) => ReportQuestao::create(['questao_id' => $questao->id, 'user_id' => $u->id, 'motivo' => 'gabarito', 'status' => 'aberto']));
        comoAdmin($this->admin);

        $this->get('/admin/questoes?suspeita=1')->assertInertia(fn ($p) => $p->where('questoes.dados.0.motivos_suspeita', ['reports'])->where('questoes.dados.0.reports_abertos', 3));

        $this->patch("/admin/reports/{$reports[0]->id}", ['status' => 'descartado'])->assertRedirect();
        expect($reports[0]->fresh()->status)->toBe('descartado')->and($reports[0]->fresh()->resolvido_em)->not->toBeNull();

        $this->patch("/admin/reports/questao/{$questao->id}", ['status' => 'resolvido'])->assertRedirect();
        expect(ReportQuestao::where('status', 'resolvido')->count())->toBe(2);
        $this->patch("/admin/reports/{$reports[1]->id}", ['status' => 'invalido'])->assertSessionHasErrors('status');

        $this->get('/admin/questoes?suspeita=1')->assertInertia(fn ($p) => $p->where('questoes.total', 0));
        expect(AdminAuditoria::where('acao', 'report_atualizado')->count())->toBe(2);
    });

    it('apaga o report junto com a conta do aluno', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];
        $aluno = User::factory()->comIdade(20)->create();
        ReportQuestao::create(['questao_id' => $questao->id, 'user_id' => $aluno->id, 'motivo' => 'outro', 'status' => 'aberto']);

        $aluno->delete();

        expect(ReportQuestao::count())->toBe(0);
    });
});

describe('retenção', function () {
    it('calcula D1, D7 e D30 por coorte semanal só com a data das respostas', function () {
        $questoes = conteudoDeTeste(3)['questoes'];
        $cadastro = now(config('gabaritu.fuso'))->subDays(10)->setTime(12, 0);
        $cadastroUtc = $cadastro->setTimezone('UTC');
        $voltou = User::factory()->comIdade(20)->create(['created_at' => $cadastroUtc]);
        User::factory()->comIdade(20)->create(['created_at' => $cadastroUtc]);
        $mesmoDia = User::factory()->comIdade(20)->create(['created_at' => $cadastroUtc]);
        respostaDireta($voltou, $questoes[0], $cadastro->addDay());
        respostaDireta($mesmoDia, $questoes[0], $cadastro->addHour());
        comoAdmin($this->admin);

        $this->get('/admin/retencao')->assertOk()->assertInertia(function ($p) {
            $coorte = collect($p->toArray()['props']['coortes'])->firstWhere('cadastros', 3);
            expect($coorte['d1'])->toMatchArray(['base' => 3, 'voltaram' => 1])->and($coorte['d1']['pct'])->toBe(33.3)
                ->and($coorte['d7']['voltaram'])->toBe(1)->and($coorte['d30']['base'])->toBe(0)->and($coorte['d30']['pct'])->toBeNull();
        });
    });
});

describe('pendências legais', function () {
    it('lista autorizações paradas, contestações, pedidos e conta apagadas por mês', function () {
        $parado = User::factory()->comIdade(15)->pendenteHaDias(8)->create(['apelido' => 'parado']);
        User::factory()->comIdade(15)->pendenteHaDias(2)->create();
        $contestado = User::factory()->comIdade(20)->create(['apelido' => 'contestou']);
        $responsavel = User::factory()->responsavel()->create();
        VinculoResponsavel::factory()->create(['aluno_id' => $contestado->id, 'responsavel_id' => $responsavel->id, 'status' => 'suspenso', 'suspenso_em' => now()]);
        ConviteResponsavel::factory()->create(['aluno_id' => $contestado->id, 'tipo' => 'pedido_vinculo', 'email' => 'pedido@exemplo.test', 'expira_em' => now()->addDays(3)]);
        ConviteResponsavel::factory()->create(['aluno_id' => $contestado->id, 'tipo' => 'autorizacao', 'expira_em' => now()->subDay()]);
        User::factory()->comIdade(20)->create()->delete();
        User::factory()->responsavel()->create()->delete();
        comoAdmin($this->admin);

        $resposta = $this->get('/admin/pendencias')->assertOk();
        expect($resposta->getContent())->not->toContain('pedido@exemplo.test');
        $resposta->assertInertia(fn ($p) => $p->component('admin/Pendencias')
            ->where('autorizacoes_paradas.total', 1)->where('autorizacoes_paradas.itens.0.apelido', 'parado')->where('autorizacoes_paradas.dias', 7)
            ->where('convites_expirados', 1)->where('contestacoes.total', 1)->where('contestacoes.itens.0.aluno', 'contestou')
            ->where('pedidos_de_responsavel.total', 1)
            ->where('contas_apagadas_por_mes.0.alunos', 1)->where('contas_apagadas_por_mes.0.responsaveis', 1));
        expect($parado)->not->toBeNull();
    });
});

describe('saúde do sistema', function () {
    it('mostra falhas sem o corpo, backup, scheduler, versão e INEP', function () {
        $pasta = sys_get_temp_dir().'/gabaritu-bkp-'.bin2hex(random_bytes(4));
        mkdir($pasta);
        touch("{$pasta}/antigo.dump", time() - 7200);
        touch("{$pasta}/novo.dump", time() - 3600);
        config(['gabaritu.admin.backup_diretorio' => $pasta, 'gabaritu.admin.versao' => 'abc123']);
        Cache::forever(SaudeDoSistema::CHAVE_DO_SCHEDULER, now()->getTimestamp());
        DB::table('failed_jobs')->insert(['uuid' => 'u-1', 'connection' => 'database', 'queue' => 'default', 'payload' => json_encode(['displayName' => 'App\\Mail\\Convite', 'data' => ['to' => 'segredo@exemplo.test']]), 'exception' => "Symfony\\Component\\Mailer\\Exception\\TransportException: falhou para segredo@exemplo.test\n#0 stack", 'failed_at' => now()]);
        conteudoDeTeste(1)['questoes'][0]->update(['origem' => 'inep', 'revisao_juridica' => 'pendente', 'origem_ref' => ['ano' => 2024]]);
        comoAdmin($this->admin);

        $resposta = $this->get('/admin/saude')->assertOk();
        expect($resposta->getContent())->not->toContain('segredo@exemplo.test');
        $resposta->assertInertia(fn ($p) => $p->component('admin/Saude')
            ->where('falhas.total', 1)->where('falhas.ultimas.0.job', 'App\\Mail\\Convite')->where('falhas.ultimas.0.erro', 'Symfony\\Component\\Mailer\\Exception\\TransportException')
            ->where('backup.arquivo', 'novo.dump')->where('backup.idade_horas', 1)->where('scheduler.atrasado', false)
            ->where('versao', 'abc123')->where('inep.total', 1)->where('inep.por_ano.2024', 1));
        File::deleteDirectory($pasta);
    });

    it('marca scheduler atrasado sem heartbeat e backup não configurado', function () {
        config(['gabaritu.admin.backup_diretorio' => null]);
        comoAdmin($this->admin);

        $this->get('/admin/saude')->assertInertia(fn ($p) => $p->where('scheduler.atrasado', true)->where('backup.configurado', false)->where('versao', 'desconhecida'));
    });

    it('o agendador grava o heartbeat', function () {
        $this->artisan('schedule:run')->assertSuccessful();

        expect(Cache::get(SaudeDoSistema::CHAVE_DO_SCHEDULER))->toBeInt();
    });
});

describe('falas do Peixoto', function () {
    it('ranqueia curtidas e rejeições por fala, nível e chave', function () {
        $boa = criarFala('acerto_simples', 'Mandou bem', 'fofo');
        $ruim = criarFala('erro_simples', 'Errou feio', 'sarc');
        $aluno = User::factory()->comIdade(20)->create();
        foreach ([[$boa, true], [$boa, true], [$ruim, false]] as [$fala, $legal]) {
            DB::table('feedbacks_fala')->insert(['user_id' => $aluno->id, 'variacao_fala_id' => $fala->id, 'legal' => $legal]);
        }
        comoAdmin($this->admin);

        $this->get('/admin/falas')->assertOk()->assertInertia(fn ($p) => $p->component('admin/Falas')
            ->where('tem_feedback', true)->where('mais_curtidas.0.texto', 'Mandou bem')->where('mais_curtidas.0.votos', 2)
            ->where('mais_rejeitadas.0.texto', 'Errou feio')->where('por_nivel.0.grupo', 'fofo')->where('por_nivel.0.curtidas', 2)
            ->where('por_chave.0.grupo', 'acerto_simples'));
        expect(ChaveFala::count())->toBe(2);
    });

    it('sem feedback cai no fallback de mais exibidas', function () {
        $fala = criarFala('acerto_simples', 'Mandou bem');
        $aluno = User::factory()->comIdade(20)->create();
        respostaDireta($aluno, conteudoDeTeste(1)['questoes'][0], falaId: $fala->id);
        comoAdmin($this->admin);

        $this->get('/admin/falas')->assertInertia(fn ($p) => $p->where('tem_feedback', false)->where('mais_curtidas', [])->where('mais_exibidas.0.exibicoes', 1));
    });
});

describe('exportar CSV', function () {
    it('exporta números agregados, sem e-mail nem apelido, e audita cada exportação', function () {
        $questao = conteudoDeTeste(1)['questoes'][0];
        $aluno = User::factory()->comIdade(20)->create(['apelido' => 'apelidosecreto', 'email' => 'secreto@exemplo.test']);
        respostaDireta($aluno, $questao);
        DB::table('metricas_diarias')->insert(['dia' => '2026-11-01', 'alunos_ativos' => 3, 'logins' => 4, 'respostas' => 5, 'visitas_landing' => 6]);
        comoAdmin($this->admin);

        foreach (ExportadorDeCsv::TIPOS as $tipo) {
            $resposta = $this->get("/admin/exportar/{$tipo}")->assertOk();
            $conteudo = $resposta->streamedContent();
            expect($resposta->headers->get('content-type'))->toContain('text/csv')
                ->and($conteudo)->not->toContain('secreto@exemplo.test')->not->toContain('apelidosecreto');
        }
        expect($this->get('/admin/exportar/metricas')->streamedContent())->toContain('2026-11-01,3,4,5,6')
            ->and($this->get('/admin/exportar/questoes')->streamedContent())->toContain($questao->codigo.',autoral');
        expect(AdminAuditoria::where('acao', 'exportar_csv')->count())->toBe(6);
        $this->get('/admin/exportar/usuarios')->assertNotFound();
    });

    it('protege células que a planilha leria como fórmula', function () {
        expect(ExportadorDeCsv::proteger('=1+1'))->toBe("'=1+1")->and(ExportadorDeCsv::proteger('-3x'))->toBe("'-3x")
            ->and(ExportadorDeCsv::proteger(12))->toBe(12)->and(ExportadorDeCsv::proteger('normal'))->toBe('normal');
    });

    it('exige login e 2FA', function () {
        $this->get('/admin/exportar/metricas')->assertRedirect('/admin/entrar');
    });
});
