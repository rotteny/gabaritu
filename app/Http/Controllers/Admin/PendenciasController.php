<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\PendenciasLegais;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PendenciasController extends Controller
{
    public function __invoke(PendenciasLegais $pendencias): Response
    {
        return Inertia::render('admin/Pendencias', $pendencias->resumo());
    }
}
