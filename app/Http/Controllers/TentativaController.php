<?php

namespace App\Http\Controllers;

use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Tentativa\ApresentadorDeQuestao;
use App\Domain\Tentativa\CriadorDeTentativa;
use App\Domain\Tentativa\FinalizadorDeTentativa;
use App\Domain\Tentativa\MontadorDeResultado;
use App\Models\Questionario;
use App\Models\Tentativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TentativaController extends Controller
{
    private const string TITULO_REVISAO = 'Revisão';

    public function store(Request $request, Questionario $questionario, CriadorDeTentativa $criador): RedirectResponse
    {
        $tentativa = $criador->doQuestionario($request->user(), $questionario);

        return redirect()->route('tentativas.show', $tentativa);
    }

    public function revisarErradas(Request $request, Tentativa $tentativa, CriadorDeTentativa $criador): RedirectResponse
    {
        Gate::authorize('ver', $tentativa);
        $revisao = $criador->dasErradas($request->user(), $tentativa);

        return $revisao
            ? redirect()->route('tentativas.show', $revisao)
            : redirect()->route('tentativas.resultado', $tentativa);
    }

    public function show(
        Request $request,
        Tentativa $tentativa,
        ApresentadorDeQuestao $apresentador,
        FinalizadorDeTentativa $finalizador,
        FalaService $falas,
    ): Response|RedirectResponse {
        Gate::authorize('ver', $tentativa);
        $item = $apresentador->itemAtual($tentativa);

        if ($item === null) {
            $finalizador->finalizarSeCompleta($tentativa);

            return redirect()->route('tentativas.resultado', $tentativa);
        }

        $apresentador->marcarServida($item);
        $questao = $item->questao;
        $assunto = $questao->assunto;
        $posicao = $tentativa->respostas()->count() + 1;

        return Inertia::render('Questao', [
            'tentativa' => [
                'id' => $tentativa->id,
                'tipo' => $tentativa->ehRevisao() ? Tentativa::TIPO_REVISAO : Tentativa::TIPO_QUESTIONARIO,
                'titulo' => $tentativa->questionario?->titulo ?? self::TITULO_REVISAO,
                'materia' => ['slug' => $assunto->materia->slug, 'nome' => $assunto->materia->nome],
                'assunto' => ['slug' => $assunto->slug, 'nome' => $assunto->nome],
                'posicao' => $posicao,
                'total' => $tentativa->itens()->count(),
            ],
            'questao' => $apresentador->questao($tentativa, $questao),
            'fala' => $falas->sortear($request->user(), [$posicao === 1 ? 'boas_vindas' : 'proxima'], Fala::HUMOR_NEUTRO),
        ]);
    }

    public function resultado(Request $request, Tentativa $tentativa, MontadorDeResultado $montador): Response|RedirectResponse
    {
        Gate::authorize('ver', $tentativa);

        if ($tentativa->finalizada_em === null) {
            return redirect()->route('tentativas.show', $tentativa);
        }

        return Inertia::render('Resultado', $montador->montar($request->user(), $tentativa));
    }
}
