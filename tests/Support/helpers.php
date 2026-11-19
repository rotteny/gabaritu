<?php

use App\Models\Alternativa;
use App\Models\Assunto;
use App\Models\ChaveFala;
use App\Models\Etiqueta;
use App\Models\Materia;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\Tentativa;
use App\Models\User;
use App\Models\VariacaoFala;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

/**
 * @return array{materia: Materia, assunto: Assunto, questionario: Questionario, questoes: Collection<int, Questao>, etiqueta: Etiqueta}
 */
function conteudoDeTeste(int $questoes = 5, int $dificuldade = 2, string $sufixo = 'a'): array
{
    $materia = Materia::create(['slug' => "mat-{$sufixo}", 'nome' => "Matéria {$sufixo}", 'area' => 'Matemática e suas Tecnologias', 'ordem' => 1]);
    $assunto = Assunto::create(['materia_id' => $materia->id, 'slug' => "ass-{$sufixo}", 'nome' => "Assunto {$sufixo}", 'ordem' => 1]);
    $etiqueta = Etiqueta::create(['materia_id' => $materia->id, 'codigo' => "mat-erro-{$sufixo}", 'nome' => 'Erro de teste']);
    $questionario = Questionario::create(['assunto_id' => $assunto->id, 'titulo' => 'Questionário 1', 'ordem' => 1]);

    $lista = collect(range(1, $questoes))->map(function (int $i) use ($assunto, $questionario, $etiqueta, $dificuldade, $sufixo): Questao {
        $questao = Questao::create([
            'codigo' => "q-{$sufixo}-{$i}",
            'assunto_id' => $assunto->id,
            'questionario_id' => $questionario->id,
            'dificuldade' => $dificuldade,
            'enunciado' => "Enunciado {$i}",
            'explicacao' => "Explicação {$i}",
            'chute_limite_ms' => 3000,
        ]);
        foreach (str_split('ABCDE') as $posicao => $letra) {
            Alternativa::create([
                'questao_id' => $questao->id,
                'letra' => $letra,
                'texto' => "Alternativa {$letra} da {$i}",
                'correta' => $posicao === 0,
                'etiqueta_id' => $posicao === 0 ? null : $etiqueta->id,
            ]);
        }

        return $questao;
    });

    return compact('materia', 'assunto', 'questionario', 'etiqueta') + ['questoes' => $lista];
}

function criarFala(string $chave, string $texto, string $nivel = 'fofo', bool $reserva = false, int $idadeMinima = 13): VariacaoFala
{
    $chaveFala = ChaveFala::firstOrCreate(['chave' => $chave]);

    return VariacaoFala::create([
        'chave_fala_id' => $chaveFala->id,
        'hash' => sha1($chave.$nivel.$texto.(int) $reserva),
        'texto' => $texto,
        'nivel' => $nivel,
        'idade_minima' => $idadeMinima,
        'reserva' => $reserva,
    ]);
}

function iniciarTentativa(User $aluno, Questionario $questionario): Tentativa
{
    test()->actingAs($aluno)->post("/questionarios/{$questionario->id}/tentativas")->assertRedirect();

    return Tentativa::query()->where('user_id', $aluno->id)->latest('id')->firstOrFail();
}

/** Abre a questão atual (grava servida_em) e devolve a questão e as alternativas como o aluno as vê. */
function abrirQuestaoAtual(User $aluno, Tentativa $tentativa): array
{
    $resposta = test()->actingAs($aluno)->get("/tentativas/{$tentativa->id}");
    $resposta->assertOk();

    return $resposta->inertiaProps('questao');
}

/** Responde a questão atual; $certa escolhe a alternativa correta (ou uma errada) e $esperarMs simula o tempo de leitura. */
function responderAtual(User $aluno, Tentativa $tentativa, bool $certa, int $esperarMs = 10_000): TestResponse
{
    $questao = abrirQuestaoAtual($aluno, $tentativa);
    $banco = Questao::with('alternativas')->findOrFail($questao['id']);
    $alvo = $banco->alternativas->first(fn (Alternativa $a) => $a->correta === $certa);

    test()->travel($esperarMs)->milliseconds();

    return test()->actingAs($aluno)->postJson("/tentativas/{$tentativa->id}/respostas", [
        'questao_id' => $questao['id'],
        'alternativa_id' => $alvo->id,
    ]);
}
