<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#35c9ee">

        <meta name="description" content="{{ $meta['descricao'] }}">
        <link rel="canonical" href="{{ $meta['url'] }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Gabaritu">
        <meta property="og:locale" content="pt_BR">
        <meta property="og:title" content="{{ $meta['titulo'] }}">
        <meta property="og:description" content="{{ $meta['descricao'] }}">
        <meta property="og:url" content="{{ $meta['url'] }}">
        <meta property="og:image" content="{{ $meta['imagem'] }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="{{ $meta['largura'] }}">
        <meta property="og:image:height" content="{{ $meta['altura'] }}">
        <meta property="og:image:alt" content="{{ $meta['imagem_alt'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $meta['titulo'] }}">
        <meta name="twitter:description" content="{{ $meta['descricao'] }}">
        <meta name="twitter:image" content="{{ $meta['imagem'] }}">
        <meta name="twitter:image:alt" content="{{ $meta['imagem_alt'] }}">

        @unless (config('gabaritu.cadastro_aberto'))
            <meta name="robots" content="noindex">
        @endunless

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $meta['titulo'] }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
