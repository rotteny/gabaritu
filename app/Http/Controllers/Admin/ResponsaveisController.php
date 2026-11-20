<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\ConsultaDeResponsaveis;
use App\Domain\Admin\EstadoDaSessao;
use App\Domain\Admin\Paginacao;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ResponsaveisController extends Controller
{
    public function __construct(private readonly ConsultaDeResponsaveis $responsaveis, private readonly Auditoria $auditoria) {}

    public function index(Request $request): Response
    {
        $busca = $request->query('busca');

        return Inertia::render('admin/Responsaveis', [
            'filtros' => ['busca' => is_string($busca) ? $busca : null],
            'responsaveis' => Paginacao::paraProps($this->responsaveis->listar(is_string($busca) ? $busca : null), $this->responsaveis->linha(...)),
        ]);
    }

    public function show(Request $request, int $responsavel): Response
    {
        $registro = $this->responsaveis->encontrar($responsavel);
        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);
        $this->auditoria->registrar($admin, AcaoDeAuditoria::AbrirResponsavel, $registro);

        return Inertia::render('admin/Responsavel', [
            'responsavel' => $this->responsaveis->linha($registro),
            'alunos' => $this->responsaveis->alunosVinculados($registro),
            'revelar' => [
                'url' => route('admin.responsaveis.revelar-email', $registro, absolute: false),
                'exige_codigo' => ! (new EstadoDaSessao($request->session()))->reconfirmadoRecentemente(),
                'email' => RevelarEmailController::emailRevelado($request, $registro),
            ],
        ]);
    }
}
