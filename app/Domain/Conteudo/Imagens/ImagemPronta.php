<?php

namespace App\Domain\Conteudo\Imagens;

final readonly class ImagemPronta
{
    public function __construct(
        public string $conteudo,
        public string $extensao,
        public int $largura,
        public int $altura,
    ) {}
}
