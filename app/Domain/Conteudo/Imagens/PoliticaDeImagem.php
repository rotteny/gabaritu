<?php

namespace App\Domain\Conteudo\Imagens;

final readonly class PoliticaDeImagem
{
    /** @param  list<string>  $extensoes */
    private function __construct(
        public array $extensoes,
        public int $limiteSvgBytes,
        public bool $altPodeFicarPendente,
    ) {}

    /** Conteúdo autoral: só SVG desenhado por nós, com alt sempre completo. */
    public static function autoral(): self
    {
        return new self(['svg'], (int) config('gabaritu.imagens.max_svg_autoral_bytes'), false);
    }

    /** INEP: também PNG e WebP (recorte da prova); alt vazio só se o item estiver marcado como alt_pendente. */
    public static function inep(bool $altPendente): self
    {
        return new self(['svg', 'png', 'webp'], (int) config('gabaritu.imagens.max_externa_bytes'), $altPendente);
    }
}
