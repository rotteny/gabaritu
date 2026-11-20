<?php

namespace App\Domain\Conteudo\Imagens;

final readonly class ImagemGravada
{
    public function __construct(
        public string $url,
        public ?string $alt,
        public int $largura,
        public int $altura,
    ) {}

    /** @return array{imagem_url: string, imagem_alt: string|null, imagem_largura: int, imagem_altura: int} */
    public function colunas(): array
    {
        return [
            'imagem_url' => $this->url,
            'imagem_alt' => $this->alt,
            'imagem_largura' => $this->largura,
            'imagem_altura' => $this->altura,
        ];
    }

    /** @return array{imagem_url: null, imagem_alt: null, imagem_largura: null, imagem_altura: null} */
    public static function colunasVazias(): array
    {
        return ['imagem_url' => null, 'imagem_alt' => null, 'imagem_largura' => null, 'imagem_altura' => null];
    }
}
