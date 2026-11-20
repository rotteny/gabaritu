<?php

use App\Support\MetaCompartilhamento;

beforeEach(fn () => config(['gabaritu.cadastro_aberto' => true]));

it('traz as tags de compartilhamento da home no HTML do servidor', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<meta property="og:title" content="Gabaritu: estude pro ENEM de graça com o Peixoto">')
        ->toContain('<meta property="og:url" content="'.url('/').'">')
        ->toContain('<link rel="canonical" href="'.url('/').'">')
        ->toContain('<meta property="og:site_name" content="Gabaritu">')
        ->toContain('<meta property="og:locale" content="pt_BR">')
        ->toContain('<meta property="og:image" content="'.url('/compartilhar.png').'?v=')
        ->toContain('<meta property="og:image:width" content="1200">')
        ->toContain('<meta property="og:image:height" content="630">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->toContain('<meta name="twitter:image" content="'.url('/compartilhar.png').'?v=')
        ->toContain('<meta name="description"')
        ->toContain('<meta name="theme-color"');
});

it('personaliza título e URL canônica nas páginas públicas', function (string $caminho, string $titulo) {
    $html = $this->get($caminho)->assertOk()->getContent();

    expect($html)
        ->toContain('<meta property="og:title" content="'.$titulo.'">')
        ->toContain('<meta property="og:url" content="'.url($caminho).'">')
        ->toContain('<meta property="og:image" content="'.url('/compartilhar.png').'?v=');
})->with([
    'cadastro' => ['/cadastro', 'Crie sua conta grátis no Gabaritu'],
    'entrar' => ['/entrar', 'Entrar no Gabaritu'],
    'termos' => ['/termos', 'Termos de uso do Gabaritu'],
    'privacidade' => ['/privacidade', 'Privacidade no Gabaritu'],
    'responsável' => ['/responsavel/entrar', 'Área do responsável | Gabaritu'],
]);

it('usa o padrão e a home como URL em rota fora do mapa, sem vazar token', function () {
    $html = $this->get('/responsavel/convite/segredo-123')->getContent();

    expect($html)
        ->toContain('<meta property="og:title" content="Gabaritu: estude pro ENEM de graça com o Peixoto">')
        ->toContain('<meta property="og:url" content="'.url('/').'">')
        ->not->toContain('<link rel="canonical" href="'.url('/responsavel/convite/segredo-123'));
});

it('mantém título ≤ 60 e descrição ≤ 155 caracteres e a imagem existe', function () {
    foreach (['home', 'register', 'login', 'termos', 'privacidade', 'quem-somos', 'responsavel.entrar', null] as $rota) {
        $meta = MetaCompartilhamento::para($rota, url('/'));

        expect(mb_strlen($meta['titulo']))->toBeLessThanOrEqual(60)
            ->and(mb_strlen($meta['descricao']))->toBeLessThanOrEqual(155);
    }

    expect(public_path(MetaCompartilhamento::IMAGEM))->toBeFile()
        ->and(filesize(public_path(MetaCompartilhamento::IMAGEM)))->toBeLessThanOrEqual(300 * 1024);
});
