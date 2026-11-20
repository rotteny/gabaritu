<?php

namespace App\Http\Controllers\Responsavel;

use App\Domain\Desempenho\DesempenhoDoAluno;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Responsavel\ApresentadorDeAluno;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PainelController extends Controller
{
    public function __construct(private readonly ApresentadorDeAluno $apresentador) {}

    public function index(Request $request, FalaService $falas): Response
    {
        $responsavel = $request->user();
        $alunos = $responsavel->alunos()->orderBy('users.id')->get()
            ->map(fn (User $aluno) => $this->apresentador->paraResponsavel($aluno, $responsavel))->all();

        return Inertia::render('responsavel/Painel', [
            'alunos' => $alunos,
            'pedidos_enviados' => $this->apresentador->pedidosEnviados($responsavel),
            'fala' => $falas->sortear($responsavel, ['responsavel'], Fala::HUMOR_NEUTRO),
        ]);
    }

    public function show(Request $request, User $aluno, DesempenhoDoAluno $desempenho, FalaService $falas): Response
    {
        $this->authorize('acompanhar', $aluno);
        $responsavel = $request->user();

        return Inertia::render('responsavel/Aluno', [
            'aluno' => $this->apresentador->paraResponsavel($aluno, $responsavel),
            ...$desempenho->montar($aluno),
            'fala' => $falas->sortear($responsavel, ['responsavel'], Fala::HUMOR_NEUTRO),
        ]);
    }
}
