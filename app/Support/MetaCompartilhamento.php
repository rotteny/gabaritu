<?php

namespace App\Support;

/**
 * Meta tags de compartilhamento (Open Graph, Twitter Card, description, canonical).
 * Saem no HTML do servidor porque os robôs de link (WhatsApp, Telegram, X...) não rodam JavaScript.
 * Um lugar só: o mapa por nome de rota. Rota fora do mapa (logada, com token, 404) cai no padrão.
 */
class MetaCompartilhamento
{
    public const string IMAGEM = 'compartilhar.png';

    public const int IMAGEM_LARGURA = 1200;

    public const int IMAGEM_ALTURA = 630;

    public const string IMAGEM_ALT = 'Peixoto, o baiacu laranja mascote do Gabaritu, ao lado do nome Gabaritu';

    /** @var array{titulo: string, descricao: string} */
    private const array PADRAO = [
        'titulo' => 'Gabaritu: estude pro ENEM de graça com o Peixoto',
        'descricao' => 'Estudar pro ENEM pode ser divertido. No Gabaritu é de graça, e o Peixoto, um baiacu exagerado, transforma cada questão numa cena de novela.',
    ];

    /** @var array<string, array{titulo: string, descricao: string}> */
    private const array PAGINAS = [
        'home' => self::PADRAO,
        'register' => [
            'titulo' => 'Crie sua conta grátis no Gabaritu',
            'descricao' => 'Cadastre-se e comece a estudar pro ENEM de graça com o Peixoto, o baiacu que zoa a resposta. Pra quem tem 13 anos ou mais.',
        ],
        'login' => [
            'titulo' => 'Entrar no Gabaritu',
            'descricao' => 'Volte a estudar pro ENEM de graça com o Peixoto. Entre na sua conta do Gabaritu e continue de onde parou.',
        ],
        'termos' => [
            'titulo' => 'Termos de uso do Gabaritu',
            'descricao' => 'As regras do jogo no Gabaritu, em português claro: o que você pode fazer, o que a gente promete e o que esperamos de quem usa.',
        ],
        'privacidade' => [
            'titulo' => 'Privacidade no Gabaritu',
            'descricao' => 'Quais dados o Gabaritu guarda, por quê e como apagar a sua conta. Plataforma gratuita, sem anúncio e pensada pra quem tem 13 anos ou mais.',
        ],
        'quem-somos' => [
            'titulo' => 'Quem faz o Gabaritu',
            'descricao' => 'Conheça quem está por trás do Gabaritu, a plataforma gratuita e open source de estudo pro ENEM com o baiacu Peixoto.',
        ],
        'responsavel.entrar' => [
            'titulo' => 'Área do responsável | Gabaritu',
            'descricao' => 'Pai, mãe ou responsável: entre para acompanhar o estudo de quem usa o Gabaritu, a plataforma gratuita de estudo pro ENEM.',
        ],
    ];

    /**
     * @return array{titulo: string, descricao: string, url: string, imagem: string, imagem_alt: string, largura: int, altura: int}
     */
    public static function para(?string $rota, string $urlAtual): array
    {
        $pagina = self::PAGINAS[$rota] ?? null;

        return ($pagina ?? self::PADRAO) + [
            'url' => $pagina === null ? url('/') : $urlAtual,
            'imagem' => self::urlDaImagem(),
            'imagem_alt' => self::IMAGEM_ALT,
            'largura' => self::IMAGEM_LARGURA,
            'altura' => self::IMAGEM_ALTURA,
        ];
    }

    /** O ?v= (mtime) força os robôs a rebuscar a imagem quando ela muda; eles guardam cache por URL. */
    private static function urlDaImagem(): string
    {
        $arquivo = public_path(self::IMAGEM);
        $versao = is_file($arquivo) ? (int) filemtime($arquivo) : 1;

        return url(self::IMAGEM).'?v='.$versao;
    }
}
