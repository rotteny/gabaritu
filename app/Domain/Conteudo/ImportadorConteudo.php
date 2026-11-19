<?php

namespace App\Domain\Conteudo;

use App\Domain\Nota\ChuteLimite;
use App\Enums\Nivel;
use App\Models\Alternativa;
use App\Models\Assunto;
use App\Models\ChaveFala;
use App\Models\Etiqueta;
use App\Models\Materia;
use App\Models\PacoteFala;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\VariacaoFala;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportadorConteudo
{
    private const array ORDEM_DAS_AREAS = [
        'Linguagens, Códigos e suas Tecnologias',
        'Ciências Humanas e suas Tecnologias',
        'Ciências da Natureza e suas Tecnologias',
        'Matemática e suas Tecnologias',
    ];

    private const array DIFICULDADE_PARA_NIVEL = [-1 => 1, 0 => 2, 1 => 3];

    private const int IDADE_MINIMA_SEM_FILTRO = 15;

    private const int LOTE_FALAS = 500;

    /** @var array<string, int> */
    private array $contagem = [];

    /** @var list<array{questao: string, falas: array<string, string>}> */
    private array $falasEspecificas = [];

    /** @return array<string, int> */
    public function importar(string $pastaMaterias, string $arquivoInterface): array
    {
        $this->contagem = ['materias' => 0, 'assuntos' => 0, 'questionarios' => 0, 'questoes' => 0, 'falas' => 0];

        DB::transaction(function () use ($pastaMaterias, $arquivoInterface): void {
            foreach ($this->pastasDeMaterias($pastaMaterias) as $ordem => $pasta) {
                $this->importarMateria($pasta, $ordem + 1);
            }
            $this->importarFalasDeInterface($arquivoInterface);
        });

        return $this->contagem;
    }

    /** @return list<string> */
    private function pastasDeMaterias(string $pasta): array
    {
        $pastas = glob(rtrim($pasta, '/').'/*/materia.json') ?: [];
        $dados = array_map(fn (string $arquivo) => [dirname($arquivo), $this->json($arquivo)], $pastas);
        usort($dados, fn (array $a, array $b) => [$this->indiceDaArea($a[1]['area']), $a[1]['slug']] <=> [$this->indiceDaArea($b[1]['area']), $b[1]['slug']]);

        return array_map(fn (array $item) => $item[0], $dados);
    }

    private function indiceDaArea(string $area): int
    {
        $indice = array_search($area, self::ORDEM_DAS_AREAS, true);

        return $indice === false ? count(self::ORDEM_DAS_AREAS) : $indice;
    }

    private function importarMateria(string $pasta, int $ordem): void
    {
        $dados = $this->json($pasta.'/materia.json');
        $materia = Materia::updateOrCreate(
            ['slug' => $dados['slug']],
            ['nome' => $dados['nome'], 'area' => $dados['area'], 'ordem' => $ordem],
        );
        $this->contagem['materias']++;

        $etiquetas = $this->importarEtiquetas($materia, $pasta);
        $assuntos = $this->importarAssuntos($materia, $dados['assuntos']);

        foreach ($assuntos as $slug => $assunto) {
            $this->importarQuestoes($assunto, $etiquetas, $pasta.'/questoes/'.$slug.'.json');
        }

        $this->importarFalasDaMateria($pasta, $materia);
    }

    /** @return array<string, int> codigo => id */
    private function importarEtiquetas(Materia $materia, string $pasta): array
    {
        foreach ($this->json($pasta.'/etiquetas.json') as $item) {
            Etiqueta::updateOrCreate(
                ['codigo' => $item['id']],
                ['materia_id' => $materia->id, 'nome' => $item['nome'], 'descricao' => $item['descricao'] ?? null],
            );
        }

        return Etiqueta::query()->where('materia_id', $materia->id)->pluck('id', 'codigo')->all();
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     * @return array<string, Assunto>
     */
    private function importarAssuntos(Materia $materia, array $itens): array
    {
        $assuntos = [];
        foreach ($itens as $item) {
            $assuntos[$item['slug']] = Assunto::updateOrCreate(
                ['materia_id' => $materia->id, 'slug' => $item['slug']],
                ['nome' => $item['nome'], 'ordem' => $item['ordem'], 'por_que' => $item['por_que'] ?? null],
            );
            $this->contagem['assuntos']++;
        }

        return $assuntos;
    }

    /** @param  array<string, int>  $etiquetas */
    private function importarQuestoes(Assunto $assunto, array $etiquetas, string $arquivo): void
    {
        $questionarios = [];
        foreach ($this->json($arquivo) as $item) {
            $ordem = (int) $item['questionario'];
            $questionarios[$ordem] ??= $this->questionario($assunto, $ordem);
            $this->importarQuestao($assunto, $questionarios[$ordem], $item, $etiquetas);
        }
    }

    private function questionario(Assunto $assunto, int $ordem): Questionario
    {
        $this->contagem['questionarios']++;

        return Questionario::updateOrCreate(
            ['assunto_id' => $assunto->id, 'ordem' => $ordem],
            ['titulo' => "Questionário {$ordem}", 'tipo' => 'assunto'],
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, int>  $etiquetas
     */
    private function importarQuestao(Assunto $assunto, Questionario $questionario, array $item, array $etiquetas): void
    {
        $textos = array_column($item['alternativas'], 'texto');
        $questao = Questao::updateOrCreate(['codigo' => $item['id']], [
            'assunto_id' => $assunto->id,
            'questionario_id' => $questionario->id,
            'dificuldade' => self::DIFICULDADE_PARA_NIVEL[(int) $item['dificuldade']],
            'contexto' => $item['contexto'] ?? null,
            'enunciado' => $item['enunciado'],
            'explicacao' => $item['explicacao'],
            'fonte' => $item['fonte'] ?? null,
            'licenca' => $item['licenca'] ?? null,
            'chute_limite_ms' => ChuteLimite::calcular($item['enunciado'], $textos),
        ]);
        $this->contagem['questoes']++;

        Alternativa::upsert(
            array_map(fn (array $alt) => [
                'questao_id' => $questao->id,
                'letra' => $alt['letra'],
                'texto' => $alt['texto'],
                'correta' => (bool) $alt['correta'],
                'etiqueta_id' => $alt['etiqueta'] ? ($etiquetas[$alt['etiqueta']] ?? throw new RuntimeException("Etiqueta desconhecida: {$alt['etiqueta']}")) : null,
            ], $item['alternativas']),
            ['questao_id', 'letra'],
            ['texto', 'correta', 'etiqueta_id'],
        );

        if (! empty($item['fala_especifica'])) {
            $this->falasEspecificas[] = ['questao' => $item['id'], 'falas' => $item['fala_especifica']];
        }
    }

    private function importarFalasDaMateria(string $pasta, Materia $materia): void
    {
        $linhas = [];
        foreach ($this->json($pasta.'/falas.json') as $item) {
            array_push($linhas, ...$this->linhasDeFalas('erro:'.$item['etiqueta'], $item));
        }
        foreach ($this->falasEspecificas as $especifica) {
            array_push($linhas, ...$this->linhasEspecificas($especifica));
        }
        $this->falasEspecificas = [];

        $this->gravarPacote('conteudo:'.$materia->slug, $linhas, critica: false);
    }

    private function importarFalasDeInterface(string $arquivo): void
    {
        $linhas = [];
        foreach ($this->json($arquivo) as $chave => $item) {
            array_push($linhas, ...$this->linhasDeFalas($chave, $item));
        }

        $this->gravarPacote('interface', $linhas, critica: false);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<array{chave: string, nivel: string, texto: string, reserva: bool}>
     */
    private function linhasDeFalas(string $chave, array $item): array
    {
        $linhas = [];
        foreach (Nivel::cases() as $nivel) {
            foreach ($item[$nivel->value] ?? [] as $texto) {
                $linhas[] = ['chave' => $chave, 'nivel' => $nivel->value, 'texto' => $texto, 'reserva' => false];
            }
        }
        if (! empty($item['reserva'])) {
            $linhas[] = ['chave' => $chave, 'nivel' => Nivel::Fofo->value, 'texto' => $item['reserva'], 'reserva' => true];
        }

        return $linhas;
    }

    /**
     * @param  array{questao: string, falas: array<string, string>}  $especifica
     * @return list<array{chave: string, nivel: string, texto: string, reserva: bool}>
     */
    private function linhasEspecificas(array $especifica): array
    {
        $linhas = [];
        foreach (Nivel::cases() as $nivel) {
            if (! empty($especifica['falas'][$nivel->value])) {
                $linhas[] = ['chave' => 'erro_especifico:'.$especifica['questao'], 'nivel' => $nivel->value, 'texto' => $especifica['falas'][$nivel->value], 'reserva' => false];
            }
        }

        return $linhas;
    }

    /** @param  list<array{chave: string, nivel: string, texto: string, reserva: bool}>  $linhas */
    private function gravarPacote(string $identificador, array $linhas, bool $critica): void
    {
        $pacote = PacoteFala::updateOrCreate(['identificador' => $identificador], ['origem' => 'manual']);
        $chaves = $this->garantirChaves(array_values(array_unique(array_column($linhas, 'chave'))), $critica);

        $registros = array_map(fn (array $linha) => [
            'chave_fala_id' => $chaves[$linha['chave']],
            'pacote_fala_id' => $pacote->id,
            'hash' => sha1(implode('|', [$linha['chave'], $linha['nivel'], (int) $linha['reserva'], $linha['texto']])),
            'texto' => $linha['texto'],
            'nivel' => $linha['nivel'],
            'idade_minima' => $linha['nivel'] === Nivel::Sem->value ? self::IDADE_MINIMA_SEM_FILTRO : (int) config('gabaritu.idade_minima'),
            'reserva' => $linha['reserva'],
            'status_revisao' => 'aprovada',
        ], $linhas);

        foreach (array_chunk($registros, self::LOTE_FALAS) as $lote) {
            VariacaoFala::upsert($lote, ['hash'], ['chave_fala_id', 'pacote_fala_id', 'texto', 'nivel', 'idade_minima', 'reserva']);
        }

        VariacaoFala::query()
            ->where('pacote_fala_id', $pacote->id)
            ->whereNotIn('hash', array_column($registros, 'hash'))
            ->delete();

        $this->contagem['falas'] += count($registros);
    }

    /**
     * @param  list<string>  $nomes
     * @return array<string, int>
     */
    private function garantirChaves(array $nomes, bool $critica): array
    {
        ChaveFala::upsert(
            array_map(fn (string $chave) => ['chave' => $chave, 'critica' => $critica || str_starts_with($chave, 'erro_http:')], $nomes),
            ['chave'],
            ['critica'],
        );

        return ChaveFala::query()->whereIn('chave', $nomes)->pluck('id', 'chave')->all();
    }

    /** @return array<mixed> */
    private function json(string $arquivo): array
    {
        $conteudo = file_get_contents($arquivo);

        if ($conteudo === false || ! json_validate($conteudo)) {
            throw new RuntimeException("JSON inválido ou ilegível: {$arquivo}");
        }

        return json_decode($conteudo, true, flags: JSON_THROW_ON_ERROR);
    }
}
