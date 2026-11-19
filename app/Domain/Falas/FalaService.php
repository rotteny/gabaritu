<?php

namespace App\Domain\Falas;

use App\Enums\Nivel;
use App\Models\ChaveFala;
use App\Models\Resposta;
use App\Models\User;
use App\Models\VariacaoFala;
use Illuminate\Database\Eloquent\Collection;

class FalaService
{
    private const string SESSAO_RECENTES = 'falas.recentes';

    private const int IDADE_ANONIMA = 13;

    /**
     * @param  list<string>  $chaves  Em ordem de preferência.
     */
    public function sortear(?User $user, array $chaves, string $humor, ?Nivel $teto = null): Fala
    {
        $nivel = $this->nivelEfetivo($user, $teto);
        $idade = $user?->idade() ?? self::IDADE_ANONIMA;
        $recentes = $user ? $this->recentes($user) : [];

        $escolhida = $this->primeiraNaoRecente($chaves, $nivel, $idade, $recentes)
            ?? $this->reserva($chaves, $nivel, $idade)
            ?? $this->qualquer($chaves, $nivel, $idade);

        if ($escolhida === null) {
            return Fala::vazia($humor);
        }

        $this->lembrar($escolhida->id);

        return new Fala($escolhida->texto, $humor, $escolhida->id);
    }

    /** @return list<int> */
    public function recentes(User $user): array
    {
        $banco = Resposta::query()
            ->where('user_id', $user->id)
            ->whereNotNull('variacao_fala_id')
            ->latest('id')
            ->limit($this->limiteRecentes())
            ->pluck('variacao_fala_id')
            ->all();

        return array_values(array_unique([...$this->daSessao(), ...$banco]));
    }

    /**
     * @param  list<string>  $chaves
     * @param  list<int>  $recentes
     */
    private function primeiraNaoRecente(array $chaves, Nivel $nivel, int $idade, array $recentes): ?VariacaoFala
    {
        foreach ($chaves as $chave) {
            $candidatas = $this->elegiveis($chave, $nivel, $idade, reserva: false)
                ->reject(fn (VariacaoFala $v) => in_array($v->id, $recentes, true));

            if ($candidatas->isNotEmpty()) {
                return $candidatas->random();
            }
        }

        return null;
    }

    /** @param  list<string>  $chaves */
    private function reserva(array $chaves, Nivel $nivel, int $idade): ?VariacaoFala
    {
        foreach ($chaves as $chave) {
            $reservas = $this->elegiveis($chave, $nivel, $idade, reserva: true);

            if ($reservas->isNotEmpty()) {
                return $reservas->random();
            }
        }

        return null;
    }

    /** @param  list<string>  $chaves */
    private function qualquer(array $chaves, Nivel $nivel, int $idade): ?VariacaoFala
    {
        foreach ($chaves as $chave) {
            $todas = $this->elegiveis($chave, $nivel, $idade, reserva: false);

            if ($todas->isNotEmpty()) {
                return $todas->random();
            }
        }

        return null;
    }

    /** @return Collection<int, VariacaoFala> */
    private function elegiveis(string $chave, Nivel $nivel, int $idade, bool $reserva): Collection
    {
        $hoje = now(config('gabaritu.fuso'))->toDateString();
        $chaveId = ChaveFala::query()->where('chave', $chave)->value('id');

        if ($chaveId === null) {
            return new Collection;
        }

        return VariacaoFala::query()
            ->where('chave_fala_id', $chaveId)
            ->where('reserva', $reserva)
            ->where('status_revisao', 'aprovada')
            ->whereIn('nivel', $nivel->ateEste())
            ->where('idade_minima', '<=', $idade)
            ->where(fn ($q) => $q->whereNull('inicio_em')->orWhere('inicio_em', '<=', $hoje))
            ->where(fn ($q) => $q->whereNull('validade_em')->orWhere('validade_em', '>=', $hoje))
            ->get();
    }

    private function nivelEfetivo(?User $user, ?Nivel $teto): Nivel
    {
        $nivel = $user?->zoeiraEfetiva() ?? Nivel::Fofo;

        return $teto ? $nivel->limitadoPor($teto) : $nivel;
    }

    /** @return list<int> */
    private function daSessao(): array
    {
        return request()->hasSession() ? array_values((array) session(self::SESSAO_RECENTES, [])) : [];
    }

    private function lembrar(int $variacaoId): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $lista = array_slice([...$this->daSessao(), $variacaoId], -$this->limiteRecentes());
        session([self::SESSAO_RECENTES => $lista]);
    }

    private function limiteRecentes(): int
    {
        return (int) config('gabaritu.falas.recentes');
    }
}
