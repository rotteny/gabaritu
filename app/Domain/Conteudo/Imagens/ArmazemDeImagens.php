<?php

namespace App\Domain\Conteudo\Imagens;

use App\Models\Alternativa;
use App\Models\Questao;
use Illuminate\Support\Facades\File;

/** Arquivos em public/conteudo/img/<sha256-12>.<ext>: nome pelo hash, então o cache pode ser longo e gravar de novo é inofensivo. */
class ArmazemDeImagens
{
    private const int TAMANHO_DO_HASH = 12;

    public function guardar(ImagemPronta $imagem): string
    {
        $nome = substr(hash('sha256', $imagem->conteudo), 0, self::TAMANHO_DO_HASH).'.'.$imagem->extensao;
        $caminho = $this->pasta().'/'.$nome;

        File::ensureDirectoryExists($this->pasta());

        if (! is_file($caminho)) {
            file_put_contents($caminho, $imagem->conteudo);
            chmod($caminho, 0644);
        }

        return rtrim((string) config('gabaritu.imagens.url_base'), '/').'/'.$nome;
    }

    /** @return list<string> arquivos removidos */
    public function removerOrfas(): array
    {
        $referenciadas = $this->urlsReferenciadas();
        $removidos = [];

        foreach (glob($this->pasta().'/*') ?: [] as $arquivo) {
            if (is_file($arquivo) && ! in_array($this->urlDe($arquivo), $referenciadas, true)) {
                unlink($arquivo);
                $removidos[] = basename($arquivo);
            }
        }

        return $removidos;
    }

    private function pasta(): string
    {
        return rtrim((string) config('gabaritu.imagens.pasta'), '/');
    }

    private function urlDe(string $arquivo): string
    {
        return rtrim((string) config('gabaritu.imagens.url_base'), '/').'/'.basename($arquivo);
    }

    /** @return list<string> */
    private function urlsReferenciadas(): array
    {
        return array_values(array_unique([
            ...Questao::query()->whereNotNull('imagem_url')->pluck('imagem_url')->all(),
            ...Alternativa::query()->whereNotNull('imagem_url')->pluck('imagem_url')->all(),
        ]));
    }
}
