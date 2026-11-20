<?php

namespace App\Domain\Conteudo\Imagens;

/** Traduz o campo `imagem` de uma questão (e de suas alternativas) do JSON nas colunas imagem_*. */
class ImagensDoItem
{
    public function __construct(private readonly PreparadorDeImagem $preparador) {}

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function daQuestao(array $item, string $pasta, PoliticaDeImagem $politica): array
    {
        return $this->colunas($item['imagem'] ?? null, $pasta, $politica);
    }

    /**
     * @param  array<string, mixed>  $alternativa
     * @return array<string, mixed>
     */
    public function daAlternativa(array $alternativa, string $pasta, PoliticaDeImagem $politica): array
    {
        return $this->colunas($alternativa['imagem'] ?? null, $pasta, $politica);
    }

    /** @return array<string, mixed> */
    private function colunas(mixed $declaracao, string $pasta, PoliticaDeImagem $politica): array
    {
        if ($declaracao === null) {
            return ImagemGravada::colunasVazias();
        }

        if (! is_array($declaracao)) {
            throw new ImagemInvalida('o campo imagem precisa ser um objeto { arquivo, alt }');
        }

        return $this->preparador
            ->preparar($declaracao, $pasta, $politica->extensoes, $politica->limiteSvgBytes, $politica->altPodeFicarPendente)
            ->colunas();
    }
}
