<?php

namespace App\Models\Concerns;

trait TemImagem
{
    /** @return array{url: string, alt: string, largura: int, altura: int}|null */
    public function imagemParaProps(): ?array
    {
        if ($this->imagem_url === null) {
            return null;
        }

        return [
            'url' => $this->imagem_url,
            'alt' => (string) $this->imagem_alt,
            'largura' => (int) $this->imagem_largura,
            'altura' => (int) $this->imagem_altura,
        ];
    }
}
