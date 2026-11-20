<?php

namespace App\Support;

use Illuminate\Http\Request;

/** `?redirect=` só vale para caminho interno (uma única barra no início): URL externa seria open redirect. */
class RedirectInterno
{
    public static function guardar(Request $request): void
    {
        $destino = $request->query('redirect');

        if (is_string($destino) && preg_match('#^/(?![/\\\\])[^\r\n]*$#', $destino) === 1) {
            $request->session()->put('url.intended', url($destino));
        }
    }
}
