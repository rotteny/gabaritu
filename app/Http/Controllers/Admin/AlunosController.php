<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\ConsultaDeAlunos;
use App\Domain\Admin\EstadoDaSessao;
use App\Domain\Admin\Paginacao;
use App\Enums\Serie;
use App\Enums\StatusConta;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AlunosController extends Controller
{
    public function __construct(private readonly ConsultaDeAlunos $alunos, private readonly Auditoria $auditoria) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['busca', 'status', 'serie', 'tem_responsavel']);

        return Inertia::render('admin/Alunos', [
            'filtros' => $filtros + ['busca' => null, 'status' => null, 'serie' => null, 'tem_responsavel' => null],
            'opcoes' => [
                'status' => array_column(StatusConta::cases(), 'value'),
                'series' => array_map(fn (Serie $s) => ['valor' => $s->value, 'rotulo' => $s->rotulo()], Serie::cases()),
            ],
            'alunos' => Paginacao::paraProps($this->alunos->listar($filtros), $this->alunos->linha(...)),
        ]);
    }

    public function show(Request $request, int $aluno): Response
    {
        $registro = $this->alunos->encontrar($aluno);
        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);
        $this->auditoria->registrar($admin, AcaoDeAuditoria::AbrirAluno, $registro);

        return Inertia::render('admin/Aluno', [
            'aluno' => $this->alunos->linha($registro),
            'responsaveis' => $this->alunos->responsaveisVinculados($registro),
            'pedidos_e_convites' => $this->alunos->pedidosEConvitesPendentes($registro),
            'desempenho' => $this->alunos->desempenhoPorMateria($registro),
            'revelar' => $this->propsDeRevelar($request, $registro, 'admin.alunos.revelar-email'),
        ]);
    }

    /** @return array{url: string, exige_codigo: bool, email: string|null} */
    private function propsDeRevelar(Request $request, User $alvo, string $rota): array
    {
        return [
            'url' => route($rota, $alvo, absolute: false),
            'exige_codigo' => ! (new EstadoDaSessao($request->session()))->reconfirmadoRecentemente(),
            'email' => RevelarEmailController::emailRevelado($request, $alvo),
        ];
    }
}
