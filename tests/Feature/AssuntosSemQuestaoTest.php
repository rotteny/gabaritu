<?php

use App\Domain\Conteudo\ImportadorConteudo;
use App\Domain\Conteudo\ImportadorInep;
use App\Models\Assunto;
use App\Models\DesempenhoAssunto;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\User;
use Illuminate\Support\Facades\File;

/** @return array{pasta: string, interface: string} */
function pastaDeMateriaSemQuestoes(): array
{
    $raiz = sys_get_temp_dir().'/gabaritu-materias-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists("{$raiz}/historia/questoes");
    file_put_contents("{$raiz}/historia/materia.json", json_encode([
        'slug' => 'historia-teste', 'nome' => 'História', 'area' => 'Ciências Humanas e suas Tecnologias',
        'assuntos' => [
            ['slug' => 'com-questoes', 'nome' => 'Com questões', 'ordem' => 1],
            ['slug' => 'so-inep', 'nome' => 'Só INEP', 'ordem' => 2, 'por_que' => 'Ainda sem questão autoral.'],
        ],
    ]));
    file_put_contents("{$raiz}/historia/etiquetas.json", json_encode([['id' => 'hist-erro', 'nome' => 'Erro', 'descricao' => 'x']]));
    file_put_contents("{$raiz}/historia/falas.json", '[]');
    file_put_contents("{$raiz}/historia/questoes/com-questoes.json", json_encode([[
        'id' => 'hist-1', 'questionario' => 1, 'dificuldade' => 0, 'enunciado' => 'Enunciado', 'explicacao' => 'Explicação',
        'alternativas' => array_map(fn (string $l) => ['letra' => $l, 'texto' => "Texto {$l}", 'correta' => $l === 'A', 'etiqueta' => $l === 'A' ? null : 'hist-erro'], str_split('ABCDE')),
    ]]));
    test()->afterEach(fn () => File::deleteDirectory($raiz));

    return ['pasta' => $raiz, 'interface' => base_path('conteudo/falas-interface.json')];
}

/** Assunto na mesma matéria cujas únicas questões são do INEP (classificadas, ativas, com questionário do INEP). */
function assuntoSoComInep(Assunto $irmao, string $slug = 'so-inep'): Assunto
{
    $assunto = Assunto::create(['materia_id' => $irmao->materia_id, 'slug' => $slug, 'nome' => 'Só INEP', 'ordem' => 2]);
    $questionario = Questionario::create(['assunto_id' => $assunto->id, 'titulo' => 'ENEM · 1', 'ordem' => 2001, 'tipo' => Questionario::TIPO_INEP]);
    $questao = Questao::create([
        'codigo' => "inep-{$slug}", 'assunto_id' => $assunto->id, 'questionario_id' => $questionario->id, 'dificuldade' => 2,
        'enunciado' => 'Enunciado', 'explicacao' => 'x', 'chute_limite_ms' => 3000, 'origem' => Questao::ORIGEM_INEP,
        'origem_ref' => ['ano' => 2024, 'aplicacao' => 'regular', 'numero' => 1], 'area' => 'CH',
    ]);
    foreach (str_split('ABCDE') as $letra) {
        $questao->alternativas()->create(['letra' => $letra, 'texto' => $letra, 'correta' => $letra === 'A']);
    }

    return $assunto;
}

/** @return list<string> */
function slugsDeAssuntosNaMateria(User $aluno, string $materia): array
{
    return array_column(test()->actingAs($aluno)->get("/materias/{$materia}")->assertOk()->inertiaProps('assuntos'), 'slug');
}

function totalDeAssuntosNoInicio(User $aluno, string $materia): int
{
    $materias = test()->actingAs($aluno)->get('/inicio')->assertOk()->inertiaProps('materias');

    return collect($materias)->firstWhere('slug', $materia)['assuntos_total'];
}

describe('importador autoral', function () {
    it('cria o assunto sem arquivo de questões e segue em frente', function () {
        $fonte = pastaDeMateriaSemQuestoes();

        $resumo = app(ImportadorConteudo::class)->importar($fonte['pasta'], $fonte['interface']);

        $vazio = Assunto::query()->where('slug', 'so-inep')->firstOrFail();
        expect($resumo['assuntos'])->toBe(2)
            ->and($resumo['questoes'])->toBe(1)
            ->and($vazio->por_que)->toBe('Ainda sem questão autoral.')
            ->and($vazio->questionarios()->count())->toBe(0)
            ->and(Questao::query()->where('assunto_id', $vazio->id)->count())->toBe(0);
    });

    it('reimportar não duplica nem apaga o que o INEP trouxe', function () {
        $fonte = pastaDeMateriaSemQuestoes();
        $importar = fn () => app(ImportadorConteudo::class)->importar($fonte['pasta'], $fonte['interface']);
        $importar();
        assuntoSoComInep(Assunto::query()->where('slug', 'com-questoes')->firstOrFail(), 'so-inep-2');

        $importar();

        expect(Assunto::count())->toBe(3)->and(Questao::query()->where('origem', Questao::ORIGEM_INEP)->count())->toBe(1);
    });
});

describe('assunto sem questão servível nas telas', function () {
    beforeEach(function () {
        $this->c = conteudoDeTeste(2);
        $this->aluno = User::factory()->create();
        $this->soInep = assuntoSoComInep($this->c['assunto']);
        $this->materia = $this->c['materia']->slug;
    });

    it('com exibir_inep desligado, some da Matéria e do Início', function () {
        config(['gabaritu.exibir_inep' => false]);

        expect(slugsDeAssuntosNaMateria($this->aluno, $this->materia))->toBe([$this->c['assunto']->slug])
            ->and(totalDeAssuntosNoInicio($this->aluno, $this->materia))->toBe(1);
    });

    it('com exibir_inep ligado e questão classificada, aparece normalmente', function () {
        config(['gabaritu.exibir_inep' => true]);

        expect(slugsDeAssuntosNaMateria($this->aluno, $this->materia))->toBe([$this->c['assunto']->slug, 'so-inep'])
            ->and(totalDeAssuntosNoInicio($this->aluno, $this->materia))->toBe(2);
    });

    it('com exibir_inep ligado mas sem questão servível (anulada), continua escondido', function () {
        config(['gabaritu.exibir_inep' => true]);
        Questao::query()->where('assunto_id', $this->soInep->id)->update(['anulada' => true]);

        expect(slugsDeAssuntosNaMateria($this->aluno, $this->materia))->toBe([$this->c['assunto']->slug])
            ->and(totalDeAssuntosNoInicio($this->aluno, $this->materia))->toBe(1);
    });

    it('some do Desempenho, mesmo com nota registrada', function (bool $exibirInep, array $esperados) {
        config(['gabaritu.exibir_inep' => $exibirInep]);
        $tentativa = iniciarTentativa($this->aluno, $this->c['questionario']);
        responderAtual($this->aluno, $tentativa, certa: true)->assertOk();
        DesempenhoAssunto::query()->insert(['user_id' => $this->aluno->id, 'assunto_id' => $this->soInep->id, 'theta' => 1.0, 'n_primeiras' => 20, 'nota' => 700]);

        $assuntos = $this->actingAs($this->aluno)->get('/desempenho')->assertOk()->inertiaProps('materias.0.assuntos');

        expect(array_column($assuntos, 'slug'))->toBe($esperados);
    })->with([
        'INEP desligado' => [false, ['ass-a']],
        'INEP ligado' => [true, ['ass-a', 'so-inep']],
    ]);

    it('a nota por matéria do painel do responsável ignora o assunto escondido', function () {
        config(['gabaritu.exibir_inep' => false]);
        $responsavel = User::factory()->responsavel()->create();
        $filho = User::factory()->comResponsavel($responsavel)->create();
        DesempenhoAssunto::query()->insert(['user_id' => $filho->id, 'assunto_id' => $this->soInep->id, 'theta' => 1.0, 'n_primeiras' => 20, 'nota' => 700]);

        $this->actingAs($responsavel)->get('/responsavel')->assertOk()->assertInertia(fn ($page) => $page->where('alunos.0.notas', []));
    });
});

describe('gabaritu:inep --remover', function () {
    it('mantém os assuntos que ficaram vazios no banco, esconde das telas e lista no relatório', function () {
        config(['gabaritu.exibir_inep' => true]);
        $c = conteudoDeTeste(1);
        $vazio = assuntoSoComInep($c['assunto']);
        $aluno = User::factory()->create();
        expect(slugsDeAssuntosNaMateria($aluno, $c['materia']->slug))->toContain('so-inep');

        $this->artisan('gabaritu:inep', ['--remover' => true, '--force' => true])
            ->expectsOutputToContain('1 assuntos ficaram sem nenhuma questão')
            ->expectsOutputToContain("{$c['materia']->slug}/so-inep")
            ->assertSuccessful();

        expect(Assunto::query()->whereKey($vazio->id)->exists())->toBeTrue()
            ->and(Questao::query()->where('assunto_id', $vazio->id)->count())->toBe(0)
            ->and(slugsDeAssuntosNaMateria($aluno, $c['materia']->slug))->toBe([$c['assunto']->slug])
            ->and(totalDeAssuntosNoInicio($aluno, $c['materia']->slug))->toBe(1);
    });

    it('não lista assunto que ainda tem questão autoral', function () {
        $c = conteudoDeTeste(1);
        $misto = assuntoSoComInep($c['assunto'], 'misto');
        $misto->questoes()->save($c['questoes']->first()->replicate(['codigo'])->fill(['codigo' => 'autoral-extra']));

        $this->artisan('gabaritu:inep', ['--remover' => true, '--force' => true])
            ->doesntExpectOutputToContain('ficaram sem nenhuma questão')
            ->assertSuccessful();
    });
});

/** @param  array<string, mixed>  $sobrescrever */
function itemDoInepReal(string $id, array $sobrescrever = []): array
{
    return [
        'id' => $id, 'origem' => 'inep', 'revisao_juridica' => 'pendente', 'area' => 'CH', 'materia' => null, 'assunto' => null,
        'origem_ref' => ['ano' => 2024, 'aplicacao' => 'regular', 'numero' => 1], 'anulada' => false, 'enunciado' => "Enunciado {$id}",
        'alternativas' => array_map(fn (string $l) => ['letra' => $l, 'texto' => $l, 'correta' => $l === 'A', 'imagem' => null], str_split('ABCDE')),
        ...$sobrescrever,
    ];
}

describe('formato real do JSON da extração do INEP', function () {
    it('lê imagem{alt_pendente} aninhado, imagens em img/, texto-base longo e ignora os arquivos intermediários', function () {
        $pasta = sys_get_temp_dir().'/gabaritu-real-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists("{$pasta}/img");
        test()->afterEach(fn () => File::deleteDirectory($pasta));
        $imagem = imagecreatetruecolor(20, 10);
        ob_start();
        imagepng($imagem);
        file_put_contents("{$pasta}/img/q1.png", (string) ob_get_clean());
        $item = itemDoInepReal('enem-2024-d1-q001', [
            'contexto' => str_repeat('Texto-base. ', 400),
            'origem_ref' => ['ano' => 2020, 'aplicacao' => 'regular_impressa_2021-01', 'numero' => 1],
            'imagem' => ['arquivo' => 'q1.png', 'alt' => null, 'alt_pendente' => true, 'largura' => 20, 'altura' => 10, 'reduzida' => false],
        ]);
        file_put_contents("{$pasta}/questoes.json", json_encode([$item]));
        file_put_contents("{$pasta}/questoes_extraidas.json", json_encode([$item, itemDoInepReal('enem-2024-d1-q002')]));
        file_put_contents("{$pasta}/falhas.json", '[]');

        $resumo = app(ImportadorInep::class)->importar($pasta);

        $questao = Questao::query()->where('codigo', 'enem-2024-d1-q001')->firstOrFail();
        expect($resumo)->toBe(['arquivos' => 1, 'questoes' => 1])
            ->and($questao->alt_pendente)->toBeTrue()
            ->and($questao->imagem_url)->not->toBeNull()
            ->and(mb_strlen($questao->contexto))->toBeGreaterThan(255)
            ->and(Questao::count())->toBe(1);
    });

    it('aceita o código da língua (en, es) na matéria de nome por extenso', function () {
        $pasta = sys_get_temp_dir().'/gabaritu-lingua-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($pasta);
        test()->afterEach(fn () => File::deleteDirectory($pasta));
        criarAssuntoDeTeste('ingles', 'leitura');
        $item = itemDoInepReal('enem-2024-d1-q001-en', ['lingua' => 'en', 'materia' => 'ingles', 'assunto' => 'leitura']);
        file_put_contents("{$pasta}/questoes.json", json_encode([$item]));

        app(ImportadorInep::class)->importar($pasta);

        expect(Questao::query()->where('codigo', 'enem-2024-d1-q001-en')->value('assunto_id'))->not->toBeNull();
    });
});
