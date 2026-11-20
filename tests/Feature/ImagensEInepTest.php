<?php

use App\Domain\Conteudo\Imagens\ImagemInvalida;
use App\Domain\Conteudo\Imagens\PreparadorDeImagem;
use App\Domain\Conteudo\Imagens\SanitizadorDeSvg;
use App\Domain\Conteudo\ImportacaoRecusada;
use App\Domain\Conteudo\ImportadorConteudo;
use App\Domain\Conteudo\ImportadorInep;
use App\Domain\Tentativa\CriadorDeTentativa;
use App\Models\Alternativa;
use App\Models\Assunto;
use App\Models\Materia;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\Resposta;
use App\Models\RevisaoEspacada;
use App\Models\User;
use Illuminate\Support\Facades\File;

const SVG_BOM = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80"><title>Reta</title><path d="M0 80 L120 0" stroke="#1b1030" fill="none"/><text x="4" y="20" font-family="Nunito, Arial, sans-serif" font-size="14">v (m/s)</text></svg>';

function pastaTemporaria(): string
{
    $pasta = sys_get_temp_dir().'/gabaritu-fonte-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($pasta);
    test()->afterEach(fn () => File::deleteDirectory($pasta));

    return $pasta;
}

function pngDeVerdade(bool $comMetadados = false): string
{
    $imagem = imagecreatetruecolor(20, 10);
    ob_start();
    imagepng($imagem);
    $png = (string) ob_get_clean();

    if (! $comMetadados) {
        return $png;
    }

    $dados = "Comment\0dados-pessoais";
    $chunk = pack('N', strlen($dados)).'tEXt'.$dados.pack('N', crc32('tEXt'.$dados));

    return substr($png, 0, 33).$chunk.substr($png, 33);
}

/** @param  array<string, mixed>  $sobrescrever */
function itemInep(string $id, array $sobrescrever = []): array
{
    return [
        'id' => $id,
        'origem' => 'inep',
        'origem_ref' => ['ano' => 2024, 'aplicacao' => 'regular', 'dia' => 1, 'caderno' => 'azul', 'cor' => 'azul', 'numero' => 7, 'url_prova' => 'https://x/p.pdf', 'url_gabarito' => 'https://x/g.pdf'],
        'revisao_juridica' => 'pendente',
        'area' => 'Ciências da Natureza e suas Tecnologias',
        'anulada' => false,
        'lingua' => null,
        'materia' => null,
        'assunto' => null,
        'enunciado' => "Enunciado {$id}",
        'alternativas' => array_map(fn (string $l) => ['letra' => $l, 'texto' => "Texto {$l}", 'correta' => $l === 'A'], str_split('ABCDE')),
        ...$sobrescrever,
    ];
}

/** @param  list<array<string, mixed>>  $itens */
function fonteInep(array $itens, string $ano = '2024'): string
{
    $pasta = pastaTemporaria();
    File::ensureDirectoryExists("{$pasta}/{$ano}");
    file_put_contents("{$pasta}/{$ano}/questoes.json", json_encode($itens));

    return $pasta;
}

function classificar(array $item, string $materia = 'fisica', string $assunto = 'cinematica'): array
{
    return [...$item, 'materia' => $materia, 'assunto' => $assunto];
}

function criarAssuntoDeTeste(string $materiaSlug = 'fisica', string $assuntoSlug = 'cinematica'): Assunto
{
    $materia = Materia::firstOrCreate(['slug' => $materiaSlug], ['nome' => ucfirst($materiaSlug), 'area' => 'Ciências da Natureza e suas Tecnologias', 'ordem' => 1]);

    return Assunto::firstOrCreate(['materia_id' => $materia->id, 'slug' => $assuntoSlug], ['nome' => ucfirst($assuntoSlug), 'ordem' => 1]);
}

dataset('svgs maliciosos', [
    'script' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script></svg>'],
    'onload' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"></svg>'],
    'on* em filho' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="1" height="1" onclick="x()"/></svg>'],
    'href externo' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><use href="https://evil.test/a.svg#x"/></svg>'],
    'xlink externo' => ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10"><use xlink:href="//evil.test/a.svg#x"/></svg>'],
    'foreignObject' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><foreignObject><div>oi</div></foreignObject></svg>'],
    'imagem base64' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><image href="data:image/png;base64,iVBORw0KGgo="/></svg>'],
    'base64 em atributo' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="1" height="1" fill="url(data:image/png;base64,AAAA)"/></svg>'],
    '@import' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><style>@import url(//evil.test/a.css);</style></svg>'],
    'url externo no fill' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="1" height="1" fill="url(https://evil.test/x)"/></svg>'],
    'DOCTYPE/entidade' => ['<!DOCTYPE svg [<!ENTITY x "y">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>'],
    'width fixo' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="100"/>'],
    'sem viewBox' => ['<svg xmlns="http://www.w3.org/2000/svg"/>'],
    'não é XML' => ['<svg><'],
]);

describe('sanitizador de SVG', function () {
    it('recusa SVG malicioso', function (string $svg) {
        expect(fn () => app(SanitizadorDeSvg::class)->sanitizar($svg))->toThrow(ImagemInvalida::class);
    })->with('svgs maliciosos');

    it('aceita o SVG didático e lê largura e altura do viewBox', function () {
        $pronta = app(SanitizadorDeSvg::class)->sanitizar('<!-- c --><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80"><defs><linearGradient id="g"><stop offset="0" stop-color="#fff"/></linearGradient></defs><rect width="10" height="10" fill="url(#g)"/><use href="#g"/></svg>');

        expect([$pronta->largura, $pronta->altura])->toBe([120, 80])->and($pronta->conteudo)->not->toContain('<!--');
    });
});

describe('imagens raster e alt', function () {
    it('recusa PNG falso', function () {
        $pasta = pastaTemporaria();
        file_put_contents("{$pasta}/falso.png", '<?php echo "oi"; ?>');

        expect(fn () => app(PreparadorDeImagem::class)->preparar(['arquivo' => 'falso.png', 'alt' => 'x'], $pasta, ['png'], 1000))
            ->toThrow(ImagemInvalida::class, 'magic bytes');
    });

    it('reprocessa o PNG e descarta os metadados', function () {
        $pasta = pastaTemporaria();
        file_put_contents("{$pasta}/ok.png", pngDeVerdade(comMetadados: true));
        expect(file_get_contents("{$pasta}/ok.png"))->toContain('dados-pessoais');

        $gravada = app(PreparadorDeImagem::class)->preparar(['arquivo' => 'ok.png', 'alt' => 'Quadro'], $pasta, ['png'], 1000);

        expect(file_get_contents($this->pastaDeImagens.'/'.basename($gravada->url)))->not->toContain('dados-pessoais')
            ->and([$gravada->largura, $gravada->altura])->toBe([20, 10])
            ->and($gravada->url)->toMatch('#^/conteudo/img/[0-9a-f]{12}\.png$#');
    });

    it('recusa imagem acima do limite', function () {
        $pasta = pastaTemporaria();
        file_put_contents("{$pasta}/grande.svg", SVG_BOM);

        expect(fn () => app(PreparadorDeImagem::class)->preparar(['arquivo' => 'grande.svg', 'alt' => 'x'], $pasta, ['svg'], 10))
            ->toThrow(ImagemInvalida::class, 'limite');
    });

    it('recusa alt vazio', function () {
        $pasta = pastaTemporaria();
        file_put_contents("{$pasta}/a.svg", SVG_BOM);

        expect(fn () => app(PreparadorDeImagem::class)->preparar(['arquivo' => 'a.svg', 'alt' => '   '], $pasta, ['svg'], 60_000))
            ->toThrow(ImagemInvalida::class, 'alt');
    });

    it('SVG autoral só aceita .svg', function () {
        $pasta = pastaTemporaria();
        file_put_contents("{$pasta}/a.png", pngDeVerdade());

        expect(fn () => app(PreparadorDeImagem::class)->preparar(['arquivo' => 'a.png', 'alt' => 'x'], $pasta, ['svg'], 60_000))
            ->toThrow(ImagemInvalida::class, 'extensão');
    });
});

describe('importador autoral', function () {
    function conteudoAutoral(array $questao): string
    {
        $raiz = pastaTemporaria();
        File::ensureDirectoryExists("{$raiz}/materias/fisica/questoes");
        File::ensureDirectoryExists("{$raiz}/materias/fisica/imagens");
        file_put_contents("{$raiz}/materias/fisica/materia.json", json_encode(['slug' => 'fisica', 'nome' => 'Física', 'area' => 'Ciências da Natureza e suas Tecnologias', 'assuntos' => [['slug' => 'cinematica', 'nome' => 'Cinemática', 'ordem' => 1]]]));
        file_put_contents("{$raiz}/materias/fisica/etiquetas.json", json_encode([['id' => 'fis-erro', 'nome' => 'Erro']]));
        file_put_contents("{$raiz}/materias/fisica/falas.json", '[]');
        file_put_contents("{$raiz}/materias/fisica/imagens/fis-001.svg", SVG_BOM);
        file_put_contents("{$raiz}/materias/fisica/imagens/ruim.svg", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><script/></svg>');
        file_put_contents("{$raiz}/materias/fisica/questoes/cinematica.json", json_encode([$questao]));
        file_put_contents("{$raiz}/interface.json", '{}');

        return $raiz;
    }

    function questaoAutoral(array $sobrescrever = []): array
    {
        return [
            'id' => 'fis-001', 'questionario' => 1, 'dificuldade' => 0, 'enunciado' => 'Veja o gráfico.', 'explicacao' => 'Porque sim.',
            'imagem' => ['arquivo' => 'fis-001.svg', 'alt' => 'Reta que sobe de 0 a 20 m/s entre 0 e 4 s.'],
            'alternativas' => array_map(fn (string $l) => ['letra' => $l, 'texto' => "Alt {$l}", 'correta' => $l === 'A', 'etiqueta' => $l === 'A' ? null : 'fis-erro'], str_split('ABCDE')),
            ...$sobrescrever,
        ];
    }

    it('copia o SVG pelo hash, grava url e alt e é idempotente', function () {
        $raiz = conteudoAutoral(questaoAutoral());
        $importar = fn () => app(ImportadorConteudo::class)->importar("{$raiz}/materias", "{$raiz}/interface.json");

        $importar();
        $arquivos = File::files($this->pastaDeImagens);
        $importar();
        $questao = Questao::where('codigo', 'fis-001')->firstOrFail();

        expect($arquivos)->toHaveCount(1)->and(File::files($this->pastaDeImagens))->toHaveCount(1)
            ->and(Questao::count())->toBe(1)
            ->and($questao->imagem_url)->toMatch('#^/conteudo/img/[0-9a-f]{12}\.svg$#')
            ->and($questao->imagem_alt)->toStartWith('Reta que sobe')
            ->and([$questao->imagem_largura, $questao->imagem_altura])->toBe([120, 80]);
    });

    it('remove arquivo órfão quando a imagem sai da questão', function () {
        $raiz = conteudoAutoral(questaoAutoral());
        app(ImportadorConteudo::class)->importar("{$raiz}/materias", "{$raiz}/interface.json");

        file_put_contents("{$raiz}/materias/fisica/questoes/cinematica.json", json_encode([questaoAutoral(['imagem' => null])]));
        app(ImportadorConteudo::class)->importar("{$raiz}/materias", "{$raiz}/interface.json");

        expect(File::files($this->pastaDeImagens))->toBeEmpty()->and(Questao::first()->imagem_url)->toBeNull();
    });

    it('recusa a importação inteira com SVG malicioso e diz qual questão', function () {
        $raiz = conteudoAutoral(questaoAutoral(['imagem' => ['arquivo' => 'ruim.svg', 'alt' => 'x']]));

        $this->artisan('gabaritu:importar-conteudo', ['--materias' => "{$raiz}/materias", '--interface' => "{$raiz}/interface.json"])
            ->expectsOutputToContain('questão fis-001')
            ->assertFailed();

        expect(Questao::count())->toBe(0);
    });

    it('recusa alt vazio na alternativa', function () {
        $alternativas = questaoAutoral()['alternativas'];
        $alternativas[1]['imagem'] = ['arquivo' => 'fis-001.svg', 'alt' => ''];
        $raiz = conteudoAutoral(questaoAutoral(['alternativas' => $alternativas]));

        $this->artisan('gabaritu:importar-conteudo', ['--materias' => "{$raiz}/materias", '--interface' => "{$raiz}/interface.json"])
            ->expectsOutputToContain('alt')
            ->assertFailed();
    });

    it('guarda a imagem da alternativa', function () {
        $alternativas = questaoAutoral()['alternativas'];
        $alternativas[1]['imagem'] = ['arquivo' => 'fis-001.svg', 'alt' => 'Gráfico B'];
        $raiz = conteudoAutoral(questaoAutoral(['alternativas' => $alternativas]));

        app(ImportadorConteudo::class)->importar("{$raiz}/materias", "{$raiz}/interface.json");

        expect(Alternativa::where('letra', 'B')->first()->imagem_alt)->toBe('Gráfico B');
    });
});

describe('importador INEP (--fonte-externa)', function () {
    it('importa, é idempotente pelo id e guarda os metadados', function () {
        $fonte = fonteInep([itemInep('enem-2024-001'), itemInep('enem-2024-002', ['anulada' => true, 'alternativas' => array_map(fn ($l) => ['letra' => $l, 'texto' => $l, 'correta' => false], str_split('ABCDE')), 'lingua' => 'ingles'])]);

        app(ImportadorInep::class)->importar($fonte);
        app(ImportadorInep::class)->importar($fonte);
        $questao = Questao::where('codigo', 'enem-2024-001')->firstOrFail();

        expect(Questao::count())->toBe(2)->and(Alternativa::count())->toBe(10)
            ->and($questao->origem)->toBe('inep')->and($questao->origem_ref['ano'])->toBe(2024)
            ->and($questao->revisao_juridica)->toBe('pendente')->and($questao->explicacao)->toBeNull()
            ->and($questao->assunto_id)->toBeNull()->and($questao->fonteParaProps())->toBe('ENEM 2024, INEP/MEC')
            ->and(Questao::where('codigo', 'enem-2024-002')->first()->status)->toBe('anulada');
    });

    it('aceita PNG com alt_pendente e recusa alt vazio sem a marca', function () {
        $fonte = fonteInep([]);
        file_put_contents("{$fonte}/2024/figura.png", pngDeVerdade());
        $importador = app(ImportadorInep::class);

        file_put_contents("{$fonte}/2024/questoes.json", json_encode([itemInep('q1', ['imagem' => ['arquivo' => 'figura.png', 'alt' => ''], 'alt_pendente' => true])]));
        $importador->importar($fonte);
        expect(Questao::first()->alt_pendente)->toBeTrue();

        file_put_contents("{$fonte}/2024/questoes.json", json_encode([itemInep('q2', ['imagem' => ['arquivo' => 'figura.png', 'alt' => '']])]));
        expect(fn () => $importador->importar($fonte))->toThrow(ImportacaoRecusada::class, 'alt');
    });

    it('recusa PNG falso na fonte externa', function () {
        $fonte = fonteInep([itemInep('q1', ['imagem' => ['arquivo' => 'falso.png', 'alt' => 'x']])]);
        file_put_contents("{$fonte}/2024/falso.png", 'GIF89a nada a ver');

        expect(fn () => app(ImportadorInep::class)->importar($fonte))
            ->toThrow(ImportacaoRecusada::class, 'magic bytes');
        expect(Questao::count())->toBe(0);
    });

    it('exige origem_ref completo e uma correta', function () {
        $fonte = fonteInep([itemInep('q1', ['origem_ref' => ['ano' => 2024]])]);

        expect(fn () => app(ImportadorInep::class)->importar($fonte))->toThrow(ImportacaoRecusada::class, 'aplicacao');
    });

    it('classifica pela materia e assunto e guarda a língua só na matéria certa', function () {
        criarAssuntoDeTeste('ingles', 'leitura');
        criarAssuntoDeTeste('espanhol', 'leitura');
        $importador = app(ImportadorInep::class);

        $importador->importar(fonteInep([classificar(itemInep('ok', ['lingua' => 'inglês']), 'ingles', 'leitura')]));
        expect(Questao::where('codigo', 'ok')->first()->assunto_id)->not->toBeNull();

        expect(fn () => $importador->importar(fonteInep([classificar(itemInep('errada', ['lingua' => 'espanhol']), 'ingles', 'leitura')])))
            ->toThrow(ImportacaoRecusada::class, 'só pode ser classificada');
    });

    it('reimportar sem classificação não desfaz a classificação existente', function () {
        criarAssuntoDeTeste();
        app(ImportadorInep::class)->importar(fonteInep([classificar(itemInep('q1'))]));
        app(ImportadorInep::class)->importar(fonteInep([itemInep('q1')]));

        expect(Questao::first()->assunto_id)->not->toBeNull();
    });

    it('monta questionários ENEM <ano> · <n> de até 10 questões, separados dos autorais', function () {
        $assunto = criarAssuntoDeTeste();
        Questionario::create(['assunto_id' => $assunto->id, 'titulo' => 'Questionário 1', 'ordem' => 1]);
        $itens = array_map(fn (int $i) => classificar(itemInep(sprintf('q%02d', $i))), range(1, 23));
        $itens[] = classificar(itemInep('q-2023', ['origem_ref' => ['ano' => 2023, 'aplicacao' => 'regular', 'numero' => 1]]));
        $itens[] = itemInep('sem-classificacao');

        app(ImportadorInep::class)->importar(fonteInep($itens));
        app(ImportadorInep::class)->importar(fonteInep($itens));

        $inep = Questionario::where('tipo', 'inep')->orderBy('ordem')->withCount('questoes')->get();

        expect($inep->pluck('titulo')->all())->toBe(['ENEM 2023 · 1', 'ENEM 2024 · 1', 'ENEM 2024 · 2', 'ENEM 2024 · 3'])
            ->and($inep->pluck('questoes_count')->all())->toBe([1, 10, 10, 3])
            ->and(Questionario::where('tipo', '!=', 'inep')->count())->toBe(1)
            ->and(Questao::where('codigo', 'sem-classificacao')->first()->questionario_id)->toBeNull();
    });
});

describe('visibilidade (GABARITU_EXIBIR_INEP)', function () {
    beforeEach(function () {
        criarAssuntoDeTeste();
        criarFala('erro', 'errou!', 'fofo');
        criarFala('boas_vindas', 'oi', 'fofo');
        $this->aluno = User::factory()->create();
        app(ImportadorInep::class)->importar(fonteInep([
            classificar(itemInep('q1')),
            classificar(itemInep('q2')),
            classificar(itemInep('anulada', ['anulada' => true])),
            classificar(itemInep('pendente', ['alt_pendente' => true])),
            classificar(itemInep('removida', ['revisao_juridica' => 'remover'])),
            itemInep('sem-classe'),
        ]));
        $this->questionarioInep = Questionario::where('tipo', 'inep')->firstOrFail();
    });

    it('padrão desligado: nada do INEP aparece em matéria, questionário, revisão nem tentativa', function () {
        expect(config('gabaritu.exibir_inep'))->toBeFalse();

        $this->actingAs($this->aluno)->get('/materias/fisica')
            ->assertInertia(fn ($page) => $page->has('assuntos', 0));
        $this->actingAs($this->aluno)->post("/questionarios/{$this->questionarioInep->id}/tentativas")->assertNotFound();
        expect(Questao::servivel()->count())->toBe(0);
    });

    it('ligada: só as classificadas, não anuladas, sem alt pendente e sem ordem de remoção', function () {
        config(['gabaritu.exibir_inep' => true]);

        expect(Questao::servivel()->pluck('codigo')->sort()->values()->all())->toBe(['q1', 'q2']);
        $this->actingAs($this->aluno)->get('/materias/fisica')
            ->assertInertia(fn ($page) => $page->has('assuntos.0.questionarios', 1)->where('assuntos.0.questionarios.0.titulo', 'ENEM 2024 · 1'));
    });

    it('revisão espaçada e tentativa de erradas respeitam a flag', function () {
        config(['gabaritu.exibir_inep' => true]);
        $tentativa = iniciarTentativa($this->aluno, $this->questionarioInep);
        responderAtual($this->aluno, $tentativa, false)->assertOk();
        expect(RevisaoEspacada::count())->toBe(1);

        config(['gabaritu.exibir_inep' => false]);
        $criador = app(CriadorDeTentativa::class);

        $this->travel(2)->days();
        expect($criador->revisaoDoDia($this->aluno))->toBeNull()
            ->and($criador->dasErradas($this->aluno, $tentativa))->toBeNull();
    });

    it('resposta errada sem etiqueta usa a fala genérica e a explicação vem nula', function () {
        config(['gabaritu.exibir_inep' => true]);
        $tentativa = iniciarTentativa($this->aluno, $this->questionarioInep);

        $resposta = responderAtual($this->aluno, $tentativa, false)->assertOk();

        expect($resposta->json('explicacao'))->toBeNull()->and($resposta->json('etiqueta'))->toBeNull()
            ->and($resposta->json('fala.texto'))->toBe('errou!')->and(Resposta::count())->toBe(1);
    });
});

describe('props', function () {
    it('Questao leva imagem, alternativas[].imagem e fonte; autoral tem fonte nula', function () {
        config(['gabaritu.exibir_inep' => true]);
        $c = conteudoDeTeste(1);
        $questao = $c['questoes']->first();
        $questao->update(['imagem_url' => '/conteudo/img/abc123abc123.svg', 'imagem_alt' => 'Gráfico', 'imagem_largura' => 120, 'imagem_altura' => 80]);
        $questao->alternativas->first()->update(['imagem_url' => '/conteudo/img/def456def456.png', 'imagem_alt' => 'Figura A', 'imagem_largura' => 20, 'imagem_altura' => 10]);
        criarFala('boas_vindas', 'oi', 'fofo');
        $aluno = User::factory()->create();
        $tentativa = iniciarTentativa($aluno, $c['questionario']);

        $props = abrirQuestaoAtual($aluno, $tentativa);
        $comImagem = collect($props['alternativas'])->filter(fn ($a) => $a['imagem'] !== null);

        expect($props['imagem'])->toBe(['url' => '/conteudo/img/abc123abc123.svg', 'alt' => 'Gráfico', 'largura' => 120, 'altura' => 80])
            ->and($props['fonte'])->toBeNull()
            ->and($comImagem)->toHaveCount(1)
            ->and($comImagem->first()['imagem'])->toBe(['url' => '/conteudo/img/def456def456.png', 'alt' => 'Figura A', 'largura' => 20, 'altura' => 10])
            ->and(collect($props['alternativas'])->where('imagem', null))->toHaveCount(4);
    });

    it('questão INEP sai com fonte "ENEM 2024, INEP/MEC" e sem imagem quando não há', function () {
        config(['gabaritu.exibir_inep' => true]);
        criarAssuntoDeTeste();
        criarFala('boas_vindas', 'oi', 'fofo');
        app(ImportadorInep::class)->importar(fonteInep([classificar(itemInep('q1'))]));
        $aluno = User::factory()->create();
        $tentativa = iniciarTentativa($aluno, Questionario::where('tipo', 'inep')->firstOrFail());

        $props = abrirQuestaoAtual($aluno, $tentativa);

        expect($props['fonte'])->toBe('ENEM 2024, INEP/MEC')->and($props['imagem'])->toBeNull();
    });
});

describe('gabaritu:inep', function () {
    it('--relatorio conta por ano, área e status', function () {
        criarAssuntoDeTeste();
        app(ImportadorInep::class)->importar(fonteInep([classificar(itemInep('q1')), itemInep('q2'), itemInep('q3', ['anulada' => true])]));

        $this->artisan('gabaritu:inep', ['--relatorio' => true])
            ->expectsOutputToContain('Total: 3')
            ->expectsOutputToContain('Ciências da Natureza e suas Tecnologias')
            ->expectsOutputToContain('sem classificação')
            ->expectsOutputToContain('anulada')
            ->assertSuccessful();
    });

    it('--remover apaga questões, respostas, imagens e arquivos e grava a auditoria', function () {
        $log = sys_get_temp_dir().'/auditoria-'.bin2hex(random_bytes(4)).'.log';
        config(['logging.channels.auditoria.path' => $log]);
        config(['gabaritu.exibir_inep' => true]);
        $c = conteudoDeTeste(1, sufixo: 'autoral');
        criarAssuntoDeTeste();
        criarFala('erro', 'errou!', 'fofo');
        criarFala('boas_vindas', 'oi', 'fofo');
        $fonte = fonteInep([classificar(itemInep('q1', ['imagem' => ['arquivo' => 'f.png', 'alt' => 'Figura']]))]);
        file_put_contents("{$fonte}/2024/f.png", pngDeVerdade());
        app(ImportadorInep::class)->importar($fonte);
        $aluno = User::factory()->create();
        responderAtual($aluno, iniciarTentativa($aluno, Questionario::where('tipo', 'inep')->firstOrFail()), true);
        expect(File::files($this->pastaDeImagens))->toHaveCount(1)->and(Resposta::count())->toBe(1);

        $this->artisan('gabaritu:inep', ['--remover' => true])->expectsConfirmation('Apagar 1 questões do INEP, as respostas a elas e as imagens?', 'no')->assertFailed();
        expect(Questao::where('origem', 'inep')->count())->toBe(1);

        $this->artisan('gabaritu:inep', ['--remover' => true, '--force' => true])->assertSuccessful();

        expect(Questao::where('origem', 'inep')->count())->toBe(0)->and(Resposta::count())->toBe(0)
            ->and(Questionario::where('tipo', 'inep')->count())->toBe(0)
            ->and(Questao::count())->toBe(1)->and(Alternativa::count())->toBe(5)
            ->and(File::files($this->pastaDeImagens))->toBeEmpty()
            ->and(file_get_contents($log))->toContain('"questoes":1')->toContain('q1')->toContain('.png');
        File::delete($log);
    });
});
