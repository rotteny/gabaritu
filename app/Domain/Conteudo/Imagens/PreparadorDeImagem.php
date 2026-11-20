<?php

namespace App\Domain\Conteudo\Imagens;

/** Valida o arquivo e o alt de uma imagem declarada no JSON e devolve o que gravar nas colunas imagem_*. */
class PreparadorDeImagem
{
    public function __construct(
        private readonly SanitizadorDeSvg $svg,
        private readonly ProcessadorDeRaster $raster,
        private readonly ArmazemDeImagens $armazem,
    ) {}

    /**
     * @param  list<string>  $extensoesAceitas
     * @param  array{arquivo?: mixed, alt?: mixed}  $declaracao
     */
    public function preparar(array $declaracao, string $pasta, array $extensoesAceitas, int $limiteSvg, bool $altPodeFicarPendente = false): ImagemGravada
    {
        $arquivo = $declaracao['arquivo'] ?? null;
        $alt = trim((string) ($declaracao['alt'] ?? ''));

        if (! is_string($arquivo) || $arquivo === '' || $arquivo !== basename($arquivo)) {
            throw new ImagemInvalida('imagem.arquivo precisa ser só o nome do arquivo');
        }

        if ($alt === '' && ! $altPodeFicarPendente) {
            throw new ImagemInvalida("imagem {$arquivo}: o alt é obrigatório e não pode ser vazio");
        }

        $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
        $pronta = $this->processar($arquivo, $this->bytesDe($pasta.'/'.$arquivo, $arquivo), $extensao, $extensoesAceitas, $limiteSvg);

        return new ImagemGravada($this->armazem->guardar($pronta), $alt === '' ? null : $alt, $pronta->largura, $pronta->altura);
    }

    /** @param  list<string>  $extensoesAceitas */
    private function processar(string $arquivo, string $bytes, string $extensao, array $extensoesAceitas, int $limiteSvg): ImagemPronta
    {
        if (! in_array($extensao, $extensoesAceitas, true)) {
            throw new ImagemInvalida("imagem {$arquivo}: extensão .{$extensao} não aceita (aceitas: ".implode(', ', $extensoesAceitas).')');
        }

        $limite = $extensao === 'svg' ? $limiteSvg : (int) config('gabaritu.imagens.max_externa_bytes');

        if (strlen($bytes) > $limite) {
            throw new ImagemInvalida("imagem {$arquivo}: ".strlen($bytes)." bytes, o limite é {$limite}");
        }

        try {
            $pronta = $extensao === 'svg' ? $this->svg->sanitizar($bytes) : $this->raster->processar($bytes, $extensao);
        } catch (ImagemInvalida $e) {
            throw new ImagemInvalida("imagem {$arquivo}: ".$e->getMessage(), previous: $e);
        }

        if (strlen($pronta->conteudo) > $limite) {
            throw new ImagemInvalida("imagem {$arquivo}: passa de {$limite} bytes depois de reprocessada");
        }

        return $pronta;
    }

    private function bytesDe(string $caminho, string $arquivo): string
    {
        $bytes = is_file($caminho) ? file_get_contents($caminho) : false;

        if ($bytes === false) {
            throw new ImagemInvalida("imagem {$arquivo}: arquivo não encontrado em {$caminho}");
        }

        return $bytes;
    }
}
