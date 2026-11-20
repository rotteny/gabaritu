<?php

namespace App\Domain\Conteudo;

use App\Domain\Conteudo\Imagens\ArmazemDeImagens;
use App\Domain\Conteudo\Imagens\ImagemInvalida;
use App\Domain\Conteudo\Imagens\ImagensDoItem;
use App\Domain\Conteudo\Imagens\PoliticaDeImagem;
use App\Domain\Nota\ChuteLimite;
use App\Models\Alternativa;
use App\Models\Assunto;
use App\Models\Materia;
use App\Models\Questao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Importa o JSON do INEP que fica fora do repositório (--fonte-externa). Idempotente pelo `id` da questão.
 * Questão sem materia/assunto fica só numa "área": não é servida até ser classificada (docs/imagens-questoes.md).
 */
class ImportadorInep
{
    /** O ENEM 2020 foi aplicado em janeiro de 2021: a extração marca como `regular_impressa_2021-01`. */
    private const string PADRAO_DE_APLICACAO = '/^(regular|ppl|reaplicacao)(_[\w-]+)?$/';

    private const array STATUS_JURIDICO = ['pendente', 'liberada', 'remover'];

    /** Intermediário da extração e relatório de falhas: não são questões a importar. */
    private const array ARQUIVOS_IGNORADOS = ['questoes_extraidas.json', 'falhas.json'];

    private const array PASTAS_DE_IMAGENS = ['img', 'imagens'];

    /** O JSON da extração traz o código da língua (en, es); as matérias usam o nome. */
    private const array LINGUA_PARA_MATERIA = ['en' => 'ingles', 'es' => 'espanhol'];

    private const string LETRAS = 'ABCDE';

    private const int DIFICULDADE_PADRAO = 2;

    private const array DIFICULDADE_PARA_NIVEL = [-1 => 1, 0 => 2, 1 => 3];

    /** @var list<string> */
    private array $erros = [];

    public function __construct(
        private readonly ImagensDoItem $imagens,
        private readonly ArmazemDeImagens $armazem,
        private readonly AgrupadorDeQuestionariosInep $agrupador,
    ) {}

    /** @return array{arquivos: int, questoes: int} */
    public function importar(string $pasta): array
    {
        if (! is_dir($pasta)) {
            throw new RuntimeException("Pasta da fonte externa não encontrada: {$pasta}");
        }

        $this->erros = [];
        $resumo = ['arquivos' => 0, 'questoes' => 0];

        DB::transaction(function () use ($pasta, &$resumo): void {
            foreach ($this->arquivosJson($pasta) as $arquivo) {
                $resumo['arquivos']++;
                $resumo['questoes'] += $this->importarArquivo($arquivo);
            }

            if ($this->erros !== []) {
                throw new ImportacaoRecusada($this->erros);
            }

            $this->agrupador->agrupar();
        });

        $this->armazem->removerOrfas();

        return $resumo;
    }

    /** @return list<string> */
    private function arquivosJson(string $pasta): array
    {
        $arquivos = [];
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterador as $arquivo) {
            if ($arquivo->isFile() && strtolower($arquivo->getExtension()) === 'json' && ! in_array($arquivo->getFilename(), self::ARQUIVOS_IGNORADOS, true)) {
                $arquivos[] = $arquivo->getPathname();
            }
        }
        sort($arquivos);

        return $arquivos;
    }

    private function importarArquivo(string $arquivo): int
    {
        $total = 0;

        foreach ($this->itensDe($arquivo) as $item) {
            try {
                $this->importarQuestao($item, dirname($arquivo));
                $total++;
            } catch (ImagemInvalida|InvalidArgumentException $e) {
                $this->erros[] = 'questão '.($item['id'] ?? '?').' ('.basename($arquivo).'): '.$e->getMessage();
            }
        }

        return $total;
    }

    /** @return list<array<string, mixed>> */
    private function itensDe(string $arquivo): array
    {
        try {
            $dados = json_decode((string) file_get_contents($arquivo), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException("JSON inválido ou ilegível: {$arquivo}");
        }

        $itens = is_array($dados) && array_is_list($dados) ? $dados : ($dados['questoes'] ?? null);

        return is_array($itens) ? array_values(array_filter($itens, 'is_array')) : [];
    }

    /** @param  array<string, mixed>  $item */
    private function importarQuestao(array $item, string $pasta): void
    {
        $this->validar($item);
        $altPendente = $this->altPendente($item);
        $politica = PoliticaDeImagem::inep($altPendente);
        $imagem = $this->imagens->daQuestao($item, $this->pastaDa($item, $pasta), $politica);
        $linhas = array_map(fn (array $alt) => [
            'letra' => $alt['letra'],
            'texto' => (string) ($alt['texto'] ?? ''),
            'correta' => (bool) ($alt['correta'] ?? false),
            'etiqueta_id' => null,
            ...$this->imagens->daAlternativa($alt, $this->pastaDa($alt, $pasta), $politica),
        ], $item['alternativas']);

        $atributos = $this->atributos($item, $altPendente, array_column($linhas, 'texto'));
        if (($item['revisao_juridica'] ?? 'pendente') === 'pendente' && $this->temDecisaoJuridica($item['id'])) {
            unset($atributos['revisao_juridica']); // a decisão tomada no painel admin sobrevive ao reimport
        }

        $questao = Questao::updateOrCreate(['codigo' => $item['id']], [
            ...$atributos,
            ...$this->classificacao($item),
            ...$imagem,
        ]);

        Alternativa::upsert(
            array_map(fn (array $linha) => ['questao_id' => $questao->id, ...$linha], $linhas),
            ['questao_id', 'letra'],
            ['texto', 'correta', 'etiqueta_id', 'imagem_url', 'imagem_alt', 'imagem_largura', 'imagem_altura'],
        );
    }

    private function temDecisaoJuridica(string $codigo): bool
    {
        return Questao::query()->where('codigo', $codigo)->whereNotNull('revisao_juridica')->where('revisao_juridica', '!=', 'pendente')->exists();
    }

    /**
     * O flag pode vir no item ou dentro de qualquer `imagem` (enunciado ou alternativa), como no JSON da extração.
     *
     * @param  array<string, mixed>  $item
     */
    private function altPendente(array $item): bool
    {
        $imagens = [$item['imagem'] ?? null, ...array_column($item['alternativas'] ?? [], 'imagem')];

        return (bool) ($item['alt_pendente'] ?? false)
            || array_any($imagens, fn ($imagem) => is_array($imagem) && ! empty($imagem['alt_pendente']));
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $textos
     * @return array<string, mixed>
     */
    private function atributos(array $item, bool $altPendente, array $textos): array
    {
        $anulada = (bool) ($item['anulada'] ?? false);

        return [
            'origem' => Questao::ORIGEM_INEP,
            'origem_ref' => $item['origem_ref'],
            'revisao_juridica' => $item['revisao_juridica'] ?? 'pendente',
            'alt_pendente' => $altPendente,
            'anulada' => $anulada,
            'status' => $anulada ? Questao::STATUS_ANULADA : Questao::STATUS_ATIVA,
            'area' => $item['area'],
            'lingua' => $item['lingua'] ?? null,
            'dificuldade' => self::DIFICULDADE_PARA_NIVEL[(int) ($item['dificuldade'] ?? 0)] ?? self::DIFICULDADE_PADRAO,
            'contexto' => $item['contexto'] ?? null,
            'enunciado' => $item['enunciado'],
            'explicacao' => $item['explicacao'] ?? null,
            'fonte' => "ENEM {$item['origem_ref']['ano']}, INEP/MEC",
            'licenca' => null,
            'chute_limite_ms' => ChuteLimite::calcular($item['enunciado'], $textos),
        ];
    }

    /**
     * Sem materia/assunto no JSON, não mexe na classificação que a questão já tiver (reimportar não a desfaz).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, int>
     */
    private function classificacao(array $item): array
    {
        if (empty($item['materia']) || empty($item['assunto'])) {
            return [];
        }

        $materia = Materia::query()->where('slug', $item['materia'])->first();
        $assunto = $materia ? Assunto::query()->where('materia_id', $materia->id)->where('slug', $item['assunto'])->first() : null;

        if ($assunto === null) {
            throw new InvalidArgumentException("materia/assunto desconhecidos: {$item['materia']}/{$item['assunto']}");
        }

        $this->exigirLinguaCompativel($item['lingua'] ?? null, $materia->slug);

        return ['assunto_id' => $assunto->id];
    }

    /** Questão de inglês só vive em Inglês, e a de espanhol só em Espanhol. */
    private function exigirLinguaCompativel(?string $lingua, string $slugDaMateria): void
    {
        $lingua = $lingua === null ? null : Str::ascii(mb_strtolower($lingua));
        $lingua = self::LINGUA_PARA_MATERIA[$lingua] ?? $lingua;

        if ($lingua !== null && $lingua !== '' && $lingua !== $slugDaMateria) {
            throw new InvalidArgumentException("questão de língua {$lingua} só pode ser classificada na matéria {$lingua} (veio {$slugDaMateria})");
        }
    }

    /** @param  array<string, mixed>  $declarante */
    private function pastaDa(array $declarante, string $pasta): string
    {
        $arquivo = $declarante['imagem']['arquivo'] ?? null;

        foreach (is_string($arquivo) ? self::PASTAS_DE_IMAGENS : [] as $subpasta) {
            if (is_file("{$pasta}/{$subpasta}/{$arquivo}")) {
                return "{$pasta}/{$subpasta}";
            }
        }

        return $pasta;
    }

    /** @param  array<string, mixed>  $item */
    private function validar(array $item): void
    {
        $this->exigir(is_string($item['id'] ?? null) && $item['id'] !== '', 'id obrigatório');
        $this->exigir(($item['origem'] ?? Questao::ORIGEM_INEP) === Questao::ORIGEM_INEP, 'origem precisa ser "inep"');
        $this->exigir(is_string($item['enunciado'] ?? null) && trim($item['enunciado']) !== '', 'enunciado obrigatório');
        $this->exigir(is_string($item['area'] ?? null) && $item['area'] !== '', 'area obrigatória (a questão sem classificação fica numa área)');
        $this->exigir(in_array($item['revisao_juridica'] ?? 'pendente', self::STATUS_JURIDICO, true), 'revisao_juridica inválida');
        $this->validarOrigemRef($item['origem_ref'] ?? null);
        $this->validarAlternativas($item['alternativas'] ?? null, (bool) ($item['anulada'] ?? false));
    }

    private function validarOrigemRef(mixed $ref): void
    {
        $this->exigir(is_array($ref) && is_int($ref['ano'] ?? null), 'origem_ref.ano obrigatório (inteiro)');
        $this->exigir(is_string($ref['aplicacao'] ?? null) && preg_match(self::PADRAO_DE_APLICACAO, $ref['aplicacao']) === 1, 'origem_ref.aplicacao precisa ser regular, ppl ou reaplicacao (com sufixo opcional, ex.: regular_impressa_2021-01)');
        $this->exigir(isset($ref['numero']), 'origem_ref.numero obrigatório');
    }

    private function validarAlternativas(mixed $alternativas, bool $anulada): void
    {
        $this->exigir(is_array($alternativas) && array_column($alternativas, 'letra') === str_split(self::LETRAS), 'as alternativas precisam ser A a E, nessa ordem');

        $corretas = count(array_filter($alternativas, fn (array $alt) => ! empty($alt['correta'])));
        $this->exigir($anulada ? $corretas <= 1 : $corretas === 1, 'precisa haver exatamente uma alternativa correta (questão anulada pode não ter)');
    }

    private function exigir(bool $condicao, string $mensagem): void
    {
        if (! $condicao) {
            throw new InvalidArgumentException($mensagem);
        }
    }
}
