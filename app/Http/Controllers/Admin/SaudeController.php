<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\SaudeDoSistema;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SaudeController extends Controller
{
    public function __invoke(SaudeDoSistema $saude): Response
    {
        return Inertia::render('admin/Saude', $saude->resumo());
    }
}
