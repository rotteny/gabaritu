<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\EstatisticasDeFalas;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class FalasController extends Controller
{
    public function __invoke(EstatisticasDeFalas $falas): Response
    {
        return Inertia::render('admin/Falas', $falas->resumo());
    }
}
