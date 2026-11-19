<?php

use App\Domain\Diagnostico\Diagnostico;
use App\Domain\Freio\EstadoFreio;
use App\Domain\Freio\Freio;
use App\Domain\Freio\ModoFreio;
use App\Domain\Nota\ChuteLimite;
use App\Domain\Nota\NotaCalculator;
use App\Domain\Revisao\AcaoRevisao;
use App\Domain\Revisao\EstadoRevisao;
use App\Domain\Revisao\RevisaoEspacada;
use Carbon\CarbonImmutable;

describe('nota Elo/Rasch', function () {
    it('calcula os casos do regras-nota.md', function (float $theta, int $dif, bool $acerta, int $n, bool $repetida, float $esperado) {
        $r = (new NotaCalculator)->atualizar($theta, $dif, $acerta, $n, $repetida);

        expect($r->theta)->toEqualWithDelta($esperado, 0.0001);
    })->with([
        '1 média acerta' => [0.0, 2, true, 0, false, 0.25],
        '2 média erra' => [0.0, 2, false, 0, false, -0.25],
        '3 difícil acerta' => [0.0, 3, true, 0, false, 0.3655],
        '4 fácil erra' => [0.0, 1, false, 0, false, -0.3655],
        '6 K normal' => [0.0, 2, true, 10, false, 0.125],
        '7 repetida' => [0.0, 2, true, 0, true, 0.0625],
        '8 trava no mínimo' => [-2.0, 1, false, 0, false, -2.0],
    ]);

    it('segue o exemplo da sequência e não exibe nota com 3 respostas', function () {
        $c = new NotaCalculator;
        $a = $c->atualizar(0.0, 2, true, 0, false);
        $b = $c->atualizar($a->theta, 3, false, $a->nPrimeiras, false);
        $d = $c->atualizar($b->theta, 1, true, $b->nPrimeiras, false);

        expect([$a->theta, $b->theta, $d->theta])->each->toBeFloat()
            ->and($b->theta)->toEqualWithDelta(0.0896, 0.0001)
            ->and($d->theta)->toEqualWithDelta(0.2155, 0.0001)
            ->and($d->nota)->toBe(522)
            ->and($d->nPrimeiras)->toBe(3)
            ->and($c->exibida($d->theta, $d->nPrimeiras))->toBeNull();
    });

    it('repetida não aumenta n_primeiras e nota trava em 300', function () {
        $c = new NotaCalculator;

        expect($c->atualizar(0.0, 2, true, 4, true)->nPrimeiras)->toBe(4)
            ->and($c->atualizar(-2.0, 1, false, 0, false)->nota)->toBe(300);
    });

    it('exibe a nota só a partir de 6 respostas, com pouca amostra até 14', function () {
        $c = new NotaCalculator;

        expect($c->exibida(0.2155, 5))->toBeNull()
            ->and($c->exibida(0.2155, 6))->toBe(520)
            ->and($c->amostraBaixa(6))->toBeTrue()
            ->and($c->amostraBaixa(15))->toBeFalse();
    });
});

it('calcula o limite de chute proporcional ao texto, com piso e teto', function (int $caracteres, int $esperado) {
    expect(ChuteLimite::calcular(str_repeat('a', $caracteres), []))->toBe($esperado);
})->with([[10, 3000], [150, 3000], [300, 6000], [600, 8000], [5000, 8000]]);

it('conta o enunciado mais as alternativas e compara com o limite', function () {
    expect(ChuteLimite::calcular(str_repeat('a', 200), [str_repeat('b', 100)]))->toBe(6000)
        ->and(ChuteLimite::ehChute(5999, 6000))->toBeTrue()
        ->and(ChuteLimite::ehChute(6000, 6000))->toBeFalse();
});

describe('freio', function () {
    it('dispara no 3º erro seguido e sai com 2 acertos (caso 16)', function () {
        $freio = new Freio;
        $estado = new EstadoFreio;
        $modos = [];

        foreach ([false, false, true, false, false, false, true, true] as $i => $correta) {
            $t = $freio->registrar($estado, $correta, 1000 + $i);
            $estado = $t->estado;
            $modos[] = $t->modo;
        }

        expect($modos)->toBe([
            ModoFreio::Normal, ModoFreio::Normal, ModoFreio::Normal, ModoFreio::Normal, ModoFreio::Normal,
            ModoFreio::Ajuda, ModoFreio::Ajuda, ModoFreio::Saida,
        ])->and($estado->ativo)->toBeFalse();
    });

    it('não dispara de novo dentro do modo ajuda e erro zera a saída', function () {
        $freio = new Freio;
        $estado = new EstadoFreio(0, true, 1, 1000);

        $t = $freio->registrar($estado, false, 1001);

        expect($t->modo)->toBe(ModoFreio::Ajuda)->and($t->estado->acertosNaSaida)->toBe(0);
    });

    it('zera o contador após 30 minutos sem responder', function () {
        $freio = new Freio;
        $estado = new EstadoFreio(2, false, 0, 1000);

        expect($freio->registrar($estado, false, 1000 + 31 * 60)->modo)->toBe(ModoFreio::Normal)
            ->and($freio->registrar($estado, false, 1000 + 60)->modo)->toBe(ModoFreio::Ajuda);
    });
});

describe('revisão espaçada', function () {
    it('percorre as caixas 1, 2, 3 e sai (caso 13)', function () {
        $r = new RevisaoEspacada;
        $d = $r->decidir(null, false, false, false, CarbonImmutable::parse('2026-03-10'));
        expect($d->estado->caixa)->toBe(1)->and($d->estado->proximaEm->toDateString())->toBe('2026-03-11');

        $d = $r->decidir($d->estado, true, false, true, CarbonImmutable::parse('2026-03-11'));
        expect($d->estado->caixa)->toBe(2)->and($d->estado->proximaEm->toDateString())->toBe('2026-03-15');

        $d = $r->decidir($d->estado, true, false, true, CarbonImmutable::parse('2026-03-15'));
        expect($d->estado->caixa)->toBe(3)->and($d->estado->proximaEm->toDateString())->toBe('2026-03-29');

        expect($r->decidir($d->estado, true, false, true, CarbonImmutable::parse('2026-03-29'))->acao)->toBe(AcaoRevisao::Remover);
    });

    it('erro na caixa 3 volta para a caixa 1 com próxima amanhã (caso 14)', function () {
        $atual = new EstadoRevisao(3, 2, CarbonImmutable::parse('2026-03-10'));
        $d = (new RevisaoEspacada)->decidir($atual, false, false, true, CarbonImmutable::parse('2026-03-10'));

        expect($d->estado->caixa)->toBe(1)->and($d->estado->proximaEm->toDateString())->toBe('2026-03-11');
    });

    it('acerto fora da revisão não mexe e chute certo na revisão não avança', function () {
        $r = new RevisaoEspacada;
        $atual = new EstadoRevisao(2, 1, CarbonImmutable::parse('2026-03-10'));

        expect($r->decidir($atual, true, false, false, CarbonImmutable::parse('2026-03-10'))->acao)->toBe(AcaoRevisao::Manter);

        $d = $r->decidir($atual, true, true, true, CarbonImmutable::parse('2026-03-10'));
        expect($d->estado->caixa)->toBe(2)->and($d->estado->proximaEm->toDateString())->toBe('2026-03-11');
    });
});

describe('diagnóstico', function () {
    $erros = fn (array $contagens, array $recentes = []) => collect($contagens)
        ->flatMap(fn (int $n, int $etiqueta) => array_fill(0, $n, $etiqueta))
        ->values()->map(fn (int $etiqueta, int $i) => ['etiqueta_id' => $etiqueta, 'ordem' => $recentes[$etiqueta] ?? $i + 1])->all();

    it('ordena por ocorrências e desempata pelo erro mais recente (caso 11)', function () use ($erros) {
        $itens = (new Diagnostico)->calcular($erros([1 => 4, 2 => 4, 3 => 2], [2 => 100]));

        expect(array_map(fn ($i) => [$i->etiquetaId, $i->percentual], $itens))->toBe([[2, 40], [1, 40]]);
    });

    it('não diagnostica com poucos erros ou sem etiqueta frequente (caso 12)', function () use ($erros) {
        $d = new Diagnostico;

        expect($d->calcular($erros([1 => 2, 2 => 2])))->toBe([])
            ->and($d->calcular($erros([1 => 2, 2 => 1, 3 => 1, 4 => 1])))->toBe([]);
    });
});
