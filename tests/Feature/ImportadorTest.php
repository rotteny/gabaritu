<?php

use App\Models\Alternativa;
use App\Models\ChaveFala;
use App\Models\Materia;
use App\Models\Questao;
use App\Models\Questionario;
use App\Models\VariacaoFala;

it('importa o conteúdo completo e é idempotente', function () {
    $this->artisan('gabaritu:importar-conteudo')->assertSuccessful();
    $contagens = fn () => [Materia::count(), Questionario::count(), Questao::count(), Alternativa::count(), ChaveFala::count(), VariacaoFala::count()];
    $primeira = $contagens();

    $this->artisan('gabaritu:importar-conteudo')->assertSuccessful();

    expect($primeira)->toBe([14, 280, 1400, 7000, $primeira[4], $primeira[5]])
        ->and($contagens())->toBe($primeira);
});

it('monta as chaves de fala e as regras de nível, idade e reserva', function () {
    $this->artisan('gabaritu:importar-conteudo')->assertSuccessful();

    $chave = ChaveFala::where('chave', 'erro:mat-esqueceu-base')->firstOrFail();
    $variacoes = $chave->variacoes;

    expect($variacoes->where('reserva', false)->count())->toBe(15)
        ->and($variacoes->where('reserva', true)->count())->toBe(1)
        ->and($variacoes->where('nivel', 'sem')->where('reserva', false)->every(fn ($v) => $v->idade_minima === 15))->toBeTrue()
        ->and(ChaveFala::whereIn('chave', ['acerto', 'freio', 'boas_vindas', 'resultado:perfeito', 'vazio:revisao', 'erro_http:404'])->count())->toBe(6);
});

it('guarda 5 alternativas e exatamente uma correta por questão, com limite de chute', function () {
    $this->artisan('gabaritu:importar-conteudo')->assertSuccessful();

    $questao = Questao::with('alternativas')->first();

    expect($questao->alternativas)->toHaveCount(5)
        ->and($questao->alternativas->where('correta', true))->toHaveCount(1)
        ->and($questao->chute_limite_ms)->toBeBetween(3000, 8000);
});

it('mantém as falas de interface dentro das regras de conteúdo', function () {
    $falas = json_decode(file_get_contents(base_path('conteudo/falas-interface.json')), true);
    $proibidas = '/\b(doen[cç]a|c[aâ]ncer|cego|surdo|gordo|gorda|feio|feia|louco|louca|doido|burro|idiota|imbecil|religi|morr|mat[ae]r?\b|sexo|droga|porra|merda|caralho)/iu';

    foreach ($falas as $chave => $item) {
        foreach (['fofo', 'sarc', 'sem'] as $nivel) {
            if (isset($item[$nivel])) {
                expect($item[$nivel])->toHaveCount(5, "{$chave}/{$nivel}");
            }
        }
        foreach (['fofo', 'sarc', 'sem'] as $nivel) {
            foreach (array_merge($item[$nivel] ?? [], [$item['reserva']]) as $texto) {
                expect(mb_strlen($texto))->toBeLessThanOrEqual(140, "{$chave}: {$texto}")
                    ->and(preg_match($proibidas, $texto))->toBe(0, "{$chave}: {$texto}");
            }
        }
    }
});
