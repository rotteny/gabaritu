<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\RetencaoPorCoorte;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RetencaoController extends Controller
{
    public function __invoke(RetencaoPorCoorte $retencao): Response
    {
        return Inertia::render('admin/Retencao', [
            'marcos' => array_map('intval', config('gabaritu.admin.retencao.marcos')),
            'coortes' => $retencao->coortes(),
        ]);
    }
}
