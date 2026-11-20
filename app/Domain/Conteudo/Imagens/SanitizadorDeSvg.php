<?php

namespace App\Domain\Conteudo\Imagens;

use DOMAttr;
use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;

/** Lista branca de elementos e atributos: o que não está aqui é recusado, nunca "limpo" em silêncio. */
class SanitizadorDeSvg
{
    private const string NS_SVG = 'http://www.w3.org/2000/svg';

    private const string NS_XLINK = 'http://www.w3.org/1999/xlink';

    private const string NS_XML = 'http://www.w3.org/XML/1998/namespace';

    private const string NS_XMLNS = 'http://www.w3.org/2000/xmlns/';

    private const array ELEMENTOS = [
        'svg', 'g', 'defs', 'title', 'desc', 'symbol', 'use', 'path', 'rect', 'circle', 'ellipse', 'line',
        'polyline', 'polygon', 'text', 'tspan', 'marker', 'clipPath', 'linearGradient', 'radialGradient', 'stop',
    ];

    private const array ATRIBUTOS = [
        'id', 'class', 'viewBox', 'preserveAspectRatio', 'role', 'lang', 'focusable',
        'aria-label', 'aria-hidden', 'aria-labelledby', 'aria-describedby',
        'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'width', 'height', 'd', 'points',
        'dx', 'dy', 'transform', 'opacity', 'fill', 'fill-opacity', 'fill-rule', 'clip-rule',
        'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-opacity', 'stroke-miterlimit', 'font-family', 'font-size', 'font-weight', 'font-style',
        'text-anchor', 'dominant-baseline', 'letter-spacing', 'text-decoration',
        'marker-start', 'marker-mid', 'marker-end', 'markerWidth', 'markerHeight', 'markerUnits', 'refX', 'refY', 'orient',
        'clip-path', 'clipPathUnits', 'gradientUnits', 'gradientTransform', 'offset', 'stop-color', 'stop-opacity',
        'href',
    ];

    private const string ID_INTERNO = '/^#[A-Za-z_][\w.\-]*$/';

    private const string REFERENCIA_URL = '/url\(\s*([^)]*)\)/i';

    public function sanitizar(string $svg): ImagemPronta
    {
        $this->recusarDtd($svg);
        $documento = $this->carregar($svg);
        $raiz = $documento->documentElement;

        if ($raiz === null || $raiz->localName !== 'svg') {
            throw new ImagemInvalida('o elemento raiz precisa ser <svg>');
        }

        $this->recusarSeNaoPermitido($raiz, raiz: true);
        $this->percorrer($raiz);
        [$largura, $altura] = $this->dimensoesDoViewBox($raiz);

        return new ImagemPronta((string) $documento->saveXML($raiz), 'svg', $largura, $altura);
    }

    private function recusarDtd(string $svg): void
    {
        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $svg) === 1) {
            throw new ImagemInvalida('DOCTYPE/ENTITY não são permitidos no SVG');
        }
    }

    private function carregar(string $svg): DOMDocument
    {
        $documento = new DOMDocument;
        $anterior = libxml_use_internal_errors(true);

        try {
            $ok = $documento->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }

        if (! $ok) {
            throw new ImagemInvalida('o SVG não é um XML válido');
        }

        return $documento;
    }

    private function percorrer(DOMNode $no): void
    {
        foreach (iterator_to_array($no->childNodes) as $filho) {
            if ($filho instanceof DOMComment) {
                $no->removeChild($filho);
            } elseif ($filho instanceof DOMProcessingInstruction) {
                throw new ImagemInvalida('instruções de processamento não são permitidas no SVG');
            } elseif ($filho instanceof DOMElement) {
                $this->recusarSeNaoPermitido($filho, raiz: false);
                $this->percorrer($filho);
            } elseif (in_array($filho->nodeName, ['#text', '#cdata-section'], true)) {
                $this->recusarDadosEmbutidos((string) $filho->nodeValue);
            }
        }
    }

    private function recusarSeNaoPermitido(DOMElement $elemento, bool $raiz): void
    {
        $nome = $elemento->localName;

        if ($elemento->namespaceURI !== self::NS_SVG || ! in_array($nome, self::ELEMENTOS, true)) {
            $motivo = $nome === 'image' ? 'imagem embutida/externa' : 'elemento fora da lista branca';
            throw new ImagemInvalida("<{$nome}> não é permitido ({$motivo})");
        }

        foreach (iterator_to_array($elemento->attributes) as $atributo) {
            $this->validarAtributo($elemento, $atributo, $raiz);
        }

        if ($raiz && ($elemento->hasAttribute('width') || $elemento->hasAttribute('height'))) {
            throw new ImagemInvalida('o <svg> não pode ter width/height fixos: use só o viewBox');
        }
    }

    private function validarAtributo(DOMElement $elemento, DOMAttr $atributo, bool $raiz): void
    {
        $nome = $atributo->localName;
        $valor = $atributo->value;

        if (str_starts_with(strtolower($nome), 'on')) {
            throw new ImagemInvalida("atributo de evento {$atributo->nodeName} não é permitido");
        }

        match ($atributo->namespaceURI) {
            null, '' => $this->validarAtributoSvg($elemento, $nome, $valor),
            self::NS_XLINK => $this->validarHref($elemento, $nome, $valor),
            self::NS_XML => in_array($nome, ['space', 'lang'], true) ?: throw new ImagemInvalida("atributo xml:{$nome} não é permitido"),
            self::NS_XMLNS => $this->validarNamespace($valor),
            default => throw new ImagemInvalida("atributo {$atributo->nodeName} não é permitido"),
        };

        $this->recusarDadosEmbutidos($valor);
        $this->validarReferenciasUrl($atributo->nodeName, $valor);
    }

    private function validarAtributoSvg(DOMElement $elemento, string $nome, string $valor): void
    {
        if ($nome === 'href') {
            $this->validarHref($elemento, $nome, $valor);

            return;
        }

        if ($nome === 'style' || ! in_array($nome, self::ATRIBUTOS, true)) {
            throw new ImagemInvalida("atributo {$nome} não é permitido (estilos só por atributos de apresentação, sem style)");
        }
    }

    private function validarHref(DOMElement $elemento, string $nome, string $valor): void
    {
        if ($nome !== 'href' || $elemento->localName !== 'use' || preg_match(self::ID_INTERNO, $valor) !== 1) {
            throw new ImagemInvalida('href só é permitido em <use> e apontando para um #id interno');
        }
    }

    private function validarNamespace(string $valor): void
    {
        if (! in_array($valor, [self::NS_SVG, self::NS_XLINK], true)) {
            throw new ImagemInvalida("namespace {$valor} não é permitido");
        }
    }

    private function validarReferenciasUrl(string $nome, string $valor): void
    {
        if (preg_match_all(self::REFERENCIA_URL, $valor, $achados) === 0) {
            return;
        }

        foreach ($achados[1] as $alvo) {
            if (preg_match(self::ID_INTERNO, trim($alvo, " \t\"'")) !== 1) {
                throw new ImagemInvalida("url() em {$nome} só pode apontar para um #id interno");
            }
        }
    }

    private function recusarDadosEmbutidos(string $texto): void
    {
        if (preg_match('/data:|base64|javascript:|@import|expression\(/i', $texto) === 1) {
            throw new ImagemInvalida('dados embutidos (data:/base64), @import ou script não são permitidos');
        }
    }

    /** @return array{int, int} */
    private function dimensoesDoViewBox(DOMElement $raiz): array
    {
        $partes = preg_split('/[\s,]+/', trim($raiz->getAttribute('viewBox'))) ?: [];

        if (count($partes) !== 4 || array_filter($partes, fn (string $p) => ! is_numeric($p)) !== []) {
            throw new ImagemInvalida('o <svg> precisa de um viewBox com quatro números');
        }

        [$largura, $altura] = [(int) round((float) $partes[2]), (int) round((float) $partes[3])];

        if ($largura <= 0 || $altura <= 0) {
            throw new ImagemInvalida('o viewBox precisa ter largura e altura positivas');
        }

        return [$largura, $altura];
    }
}
