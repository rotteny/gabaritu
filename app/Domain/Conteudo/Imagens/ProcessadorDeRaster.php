<?php

namespace App\Domain\Conteudo\Imagens;

use GdImage;

/** PNG e WebP: confere os magic bytes e recodifica com GD, o que descarta metadados (EXIF, texto, ICC). */
class ProcessadorDeRaster
{
    private const string MAGIC_PNG = "\x89PNG\r\n\x1a\n";

    private const int QUALIDADE_WEBP_SEM_PERDA = 101;

    private const int COMPRESSAO_PNG = 9;

    private const int POSICAO_TIPO_DE_COR_PNG = 25;

    private const int PNG_CINZA_ALFA = 4;

    private const int PNG_RGBA = 6;

    public function processar(string $bytes, string $extensao): ImagemPronta
    {
        $formato = $this->formatoPelosMagicBytes($bytes);

        if ($formato === null || $formato !== $extensao) {
            throw new ImagemInvalida("o conteúdo não é um .{$extensao} de verdade (magic bytes não conferem)");
        }

        $this->conferirDimensoes($bytes);
        $imagem = @imagecreatefromstring($bytes);

        if (! $imagem instanceof GdImage) {
            throw new ImagemInvalida('não foi possível decodificar a imagem (arquivo corrompido ou animado)');
        }

        $saida = $this->recodificar($imagem, $formato, $this->temAlpha($bytes, $formato));

        return new ImagemPronta($saida, $formato, imagesx($imagem), imagesy($imagem));
    }

    private function formatoPelosMagicBytes(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, self::MAGIC_PNG) => 'png',
            strlen($bytes) >= 12 && str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'webp',
            default => null,
        };
    }

    /** PNG sem canal alfa continua sem: salvar com alfa inflaria o arquivo em cerca de um terço. */
    private function temAlpha(string $bytes, string $formato): bool
    {
        $tipoDeCor = ord($bytes[self::POSICAO_TIPO_DE_COR_PNG] ?? "\0");

        return $formato === 'png' && (in_array($tipoDeCor, [self::PNG_CINZA_ALFA, self::PNG_RGBA], true) || str_contains($bytes, 'tRNS'));
    }

    private function conferirDimensoes(string $bytes): void
    {
        $tamanho = @getimagesizefromstring($bytes);

        if ($tamanho === false || $tamanho[0] < 1 || $tamanho[1] < 1) {
            throw new ImagemInvalida('não foi possível ler as dimensões da imagem');
        }

        if ((int) config('gabaritu.imagens.max_pixels') < $tamanho[0] * $tamanho[1]) {
            throw new ImagemInvalida('a imagem tem pixels demais');
        }
    }

    private function recodificar(GdImage $imagem, string $formato, bool $temAlpha): string
    {
        if ($formato === 'webp') {
            imagepalettetotruecolor($imagem);
        }

        if ($formato === 'webp' || $temAlpha) {
            imagealphablending($imagem, false);
            imagesavealpha($imagem, true);
        }

        ob_start();
        $ok = $formato === 'png'
            ? imagepng($imagem, null, self::COMPRESSAO_PNG)
            : imagewebp($imagem, null, self::QUALIDADE_WEBP_SEM_PERDA);
        $saida = (string) ob_get_clean();

        if (! $ok || $saida === '') {
            throw new ImagemInvalida('falha ao reprocessar a imagem');
        }

        return $saida;
    }
}
