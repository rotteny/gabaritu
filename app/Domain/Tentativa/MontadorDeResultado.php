<?php

namespace App\Domain\Tentativa;

use App\Domain\Desempenho\ServicoDeNota;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Models\DesempenhoAssuntoHistorico;
use App\Models\Questionario;
use App\Models\Tentativa;
use App\Models\User;

class MontadorDeResultado
{
    private const float LIMITE_ALTO = 0.8;

    private const float LIMITE_MEDIO = 0.4;

    public function __construct(
        private readonly FalaService $falas,
        private readonly ServicoDeNota $nota,
    ) {}

    /** @return array<string, mixed> */
    public function montar(User $aluno, Tentativa $tentativa): array
    {
        $validas = $tentativa->respostas()->whereHas('questao', fn ($q) => $q->servivel());
        $total = (clone $validas)->count();
        $acertos = (clone $validas)->where('correta', true)->count();

        return [
            'tentativa_id' => $tentativa->id,
            'acertos' => $acertos,
            'total' => $total,
            'erro_mais_comum' => $this->erroMaisComum($tentativa),
            'nota_assunto' => $this->notaDoAssunto($tentativa),
            'tem_erradas' => $tentativa->respostas()->where('correta', false)->exists(),
            'tipo' => $tentativa->ehRevisao() ? Tentativa::TIPO_REVISAO : Tentativa::TIPO_QUESTIONARIO,
            'proximo_questionario' => $this->proximoQuestionario($tentativa),
            'fala' => $this->falas->sortear($aluno, ['resultado:'.$this->faixa($acertos, $total)], $this->humor($acertos, $total)),
        ];
    }

    private function faixa(int $acertos, int $total): string
    {
        $proporcao = $total > 0 ? $acertos / $total : 0.0;

        return match (true) {
            $total > 0 && $acertos === $total => 'perfeito',
            $proporcao >= self::LIMITE_ALTO => 'alto',
            $proporcao >= self::LIMITE_MEDIO => 'medio',
            default => 'baixo',
        };
    }

    private function humor(int $acertos, int $total): string
    {
        return $total > 0 && $acertos / $total >= self::LIMITE_ALTO ? Fala::HUMOR_FELIZ : Fala::HUMOR_NEUTRO;
    }

    /** @return array{etiqueta: string, nome: string, vezes: int}|null */
    private function erroMaisComum(Tentativa $tentativa): ?array
    {
        $linha = $tentativa->respostas()
            ->where('respostas.correta', false)
            ->join('alternativas', 'alternativas.id', '=', 'respostas.alternativa_id')
            ->join('etiquetas', 'etiquetas.id', '=', 'alternativas.etiqueta_id')
            ->groupBy('etiquetas.id', 'etiquetas.codigo', 'etiquetas.nome')
            ->selectRaw('etiquetas.codigo as codigo, etiquetas.nome as nome, count(*) as vezes')
            ->orderByDesc('vezes')->orderBy('etiquetas.id')
            ->first();

        $dados = $linha?->getAttributes();

        return $dados ? ['etiqueta' => (string) $dados['codigo'], 'nome' => (string) $dados['nome'], 'vezes' => (int) $dados['vezes']] : null;
    }

    /** @return array{antes: int|null, depois: int}|null */
    private function notaDoAssunto(Tentativa $tentativa): ?array
    {
        if ($tentativa->assunto_id === null || $tentativa->finalizada_em === null) {
            return null;
        }

        $primeira = (int) $tentativa->respostas()->min('id');
        $ultima = (int) $tentativa->respostas()->max('id');
        $depois = $this->snapshot($tentativa, '<=', $ultima);
        $depoisExibida = $depois ? $this->nota->exibidaDeSnapshot($depois->nota, $depois->n_primeiras) : null;

        if ($depoisExibida === null) {
            return null;
        }

        $antes = $this->snapshot($tentativa, '<', $primeira);

        return [
            'antes' => $antes ? $this->nota->exibidaDeSnapshot($antes->nota, $antes->n_primeiras) : null,
            'depois' => $depoisExibida,
        ];
    }

    private function snapshot(Tentativa $tentativa, string $operador, int $respostaId): ?DesempenhoAssuntoHistorico
    {
        return DesempenhoAssuntoHistorico::query()
            ->where('user_id', $tentativa->user_id)
            ->where('assunto_id', $tentativa->assunto_id)
            ->where('resposta_id', $operador, $respostaId)
            ->latest('id')
            ->first();
    }

    /** @return array{id: int, titulo: string}|null */
    private function proximoQuestionario(Tentativa $tentativa): ?array
    {
        $atual = $tentativa->questionario;

        if ($atual === null) {
            return null;
        }

        $proximo = Questionario::query()
            ->where('assunto_id', $atual->assunto_id)
            ->where('ordem', '>', $atual->ordem)
            ->where('tipo', $atual->tipo)
            ->when($atual->tipo === Questionario::TIPO_INEP, fn ($q) => $q->whereHas('questoes', fn ($questoes) => $questoes->servivel()))
            ->orderBy('ordem')
            ->first();

        return $proximo ? ['id' => $proximo->id, 'titulo' => $proximo->titulo] : null;
    }
}
