<?php

namespace App\Domain\Tentativa;

use App\Domain\Desempenho\ServicoDeNota;
use App\Domain\Falas\Fala;
use App\Domain\Falas\FalaService;
use App\Domain\Freio\FreioDaSessao;
use App\Domain\Freio\ModoFreio;
use App\Domain\Revisao\FilaDeRevisao;
use App\Enums\Nivel;
use App\Models\Alternativa;
use App\Models\Questao;
use App\Models\Resposta;
use App\Models\Tentativa;
use App\Models\TentativaQuestao;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResponderQuestao
{
    public function __construct(
        private readonly ServicoDeNota $nota,
        private readonly FilaDeRevisao $revisao,
        private readonly FreioDaSessao $freio,
        private readonly FalaService $falas,
        private readonly FinalizadorDeTentativa $finalizador,
    ) {}

    public function executar(User $aluno, Tentativa $tentativa, int $questaoId, int $alternativaId, Session $sessao): ResultadoDaResposta
    {
        return DB::transaction(function () use ($aluno, $tentativa, $questaoId, $alternativaId, $sessao): ResultadoDaResposta {
            $item = $this->itemPendente($tentativa, $questaoId);
            $questao = $item->questao()->with('alternativas.etiqueta')->firstOrFail();
            $escolhida = $this->alternativaDaQuestao($questao, $alternativaId);

            $resposta = $this->gravarResposta($aluno, $tentativa, $item, $questao, $escolhida);

            if (! $resposta->chute) {
                $this->nota->registrar($aluno, $questao, $resposta);
            }
            $this->revisao->aplicar($aluno, $questao, $resposta->correta, $resposta->chute, $tentativa->ehRevisao());

            $transicao = $this->freio->registrar($sessao, $resposta->correta);
            $fala = $this->escolherFala($aluno, $tentativa, $questao, $escolhida, $transicao->modo);
            $resposta->update(['variacao_fala_id' => $fala->variacaoId]);

            return new ResultadoDaResposta(
                correta: $resposta->correta,
                alternativaCorreta: $questao->alternativas->firstWhere('correta', true),
                alternativaEscolhida: $escolhida,
                explicacao: $questao->explicacao,
                fala: $fala,
                freioAtivo: $transicao->estado->ativo,
                chute: $resposta->chute,
                tentativaTerminou: $this->finalizador->finalizarSeCompleta($tentativa),
            );
        });
    }

    private function itemPendente(Tentativa $tentativa, int $questaoId): TentativaQuestao
    {
        $item = $tentativa->itens()->where('questao_id', $questaoId)->first();

        if ($item === null) {
            throw $this->recusa('Essa questão não faz parte desta tentativa.');
        }
        if ($tentativa->respostas()->where('questao_id', $questaoId)->exists()) {
            throw $this->recusa('Essa questão já foi respondida.');
        }
        if ($item->servida_em === null) {
            throw $this->recusa('Essa questão ainda não foi entregue.');
        }

        return $item;
    }

    private function alternativaDaQuestao(Questao $questao, int $alternativaId): Alternativa
    {
        return $questao->alternativas->firstWhere('id', $alternativaId)
            ?? throw $this->recusa('Essa alternativa não pertence à questão.');
    }

    private function gravarResposta(User $aluno, Tentativa $tentativa, TentativaQuestao $item, Questao $questao, Alternativa $escolhida): Resposta
    {
        $agora = now();
        $tempoMs = max(0, (int) round($item->servida_em?->diffInMilliseconds($agora, absolute: true) ?? 0));

        return Resposta::create([
            'user_id' => $aluno->id,
            'tentativa_id' => $tentativa->id,
            'questao_id' => $questao->id,
            'alternativa_id' => $escolhida->id,
            'contexto' => $tentativa->tipo,
            'correta' => $escolhida->correta,
            'chute' => $tempoMs < $questao->chute_limite_ms,
            'repetida' => Resposta::query()->where('user_id', $aluno->id)->where('questao_id', $questao->id)->where('chute', false)->exists(),
            'servida_em' => $item->servida_em,
            'respondida_em' => $agora,
            'tempo_ms' => $tempoMs,
        ]);
    }

    private function escolherFala(User $aluno, Tentativa $tentativa, Questao $questao, Alternativa $escolhida, ModoFreio $modo): Fala
    {
        return match (true) {
            $modo === ModoFreio::Saida => $this->falas->sortear($aluno, ['freio_saida'], Fala::HUMOR_NEUTRO, Nivel::Fofo),
            $modo === ModoFreio::Ajuda && ! $escolhida->correta => $this->falas->sortear($aluno, ['freio'], Fala::HUMOR_AJUDA, Nivel::Fofo),
            $escolhida->correta => $this->falaDeAcerto($aluno, $tentativa, $modo === ModoFreio::Ajuda ? Fala::HUMOR_AJUDA : Fala::HUMOR_FELIZ, $modo === ModoFreio::Ajuda ? Nivel::Fofo : null),
            default => $this->falas->sortear($aluno, array_values(array_filter([
                'erro_especifico:'.$questao->codigo,
                $escolhida->etiqueta ? 'erro:'.$escolhida->etiqueta->codigo : null,
                'erro',
            ])), Fala::HUMOR_BRAVO),
        };
    }

    private function falaDeAcerto(User $aluno, Tentativa $tentativa, string $humor, ?Nivel $teto): Fala
    {
        $chaves = [$this->houveErroAntes($tentativa) ? 'acerto_apos_erro' : 'acerto', 'acerto'];

        return $this->falas->sortear($aluno, $chaves, $humor, $teto);
    }

    private function houveErroAntes(Tentativa $tentativa): bool
    {
        $anterior = $tentativa->respostas()->latest('id')->skip(1)->first();

        return $anterior !== null && ! $anterior->correta;
    }

    private function recusa(string $mensagem): ValidationException
    {
        return ValidationException::withMessages(['questao_id' => $mensagem]);
    }
}
