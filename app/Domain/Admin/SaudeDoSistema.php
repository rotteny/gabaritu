<?php

namespace App\Domain\Admin;

use App\Models\Questao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Termômetros operacionais: e-mails que falharam, backup, scheduler, versão do deploy e totais do INEP. */
class SaudeDoSistema
{
    public const string CHAVE_DO_SCHEDULER = 'sistema:scheduler:ultimo_tick';

    private const int FALHAS_EXIBIDAS = 10;

    /** O scheduler roda a cada minuto; passado esse tempo sem tick, algo parou. */
    private const int SCHEDULER_ATRASADO_EM_MINUTOS = 5;

    /** @return array<string, mixed> */
    public function resumo(): array
    {
        return [
            'falhas' => $this->falhas(),
            'backup' => $this->backup(),
            'scheduler' => $this->scheduler(),
            'versao' => $this->versao(),
            'inep' => $this->inep(),
        ];
    }

    /** Sem o corpo da mensagem (pode ter e-mail de aluno): só quando, em qual fila, qual job e a classe do erro. */
    /** @return array{total: int, ultimas: array<int, array<string, mixed>>} */
    private function falhas(): array
    {
        $total = DB::table('failed_jobs')->count();
        $recentes = DB::table('failed_jobs')->orderByDesc('id')->limit(self::FALHAS_EXIBIDAS)->get(['id', 'queue', 'payload', 'exception', 'failed_at']);

        return [
            'total' => $total,
            'ultimas' => $recentes->map(fn (object $f) => [
                'id' => (int) $f->id,
                'fila' => $f->queue,
                'job' => json_decode($f->payload, true)['displayName'] ?? null,
                'erro' => strtok((string) $f->exception, ':') ?: null,
                'quando' => Fuso::data(Carbon::parse($f->failed_at, config('app.timezone'))),
            ])->all(),
        ];
    }

    /** @return array{configurado: bool, arquivo: string|null, quando: string|null, idade_horas: int|null} */
    private function backup(): array
    {
        $diretorio = config('gabaritu.admin.backup_diretorio');
        $arquivos = is_string($diretorio) && is_dir($diretorio) ? (glob(rtrim($diretorio, '/').'/*') ?: []) : [];
        $arquivos = array_filter($arquivos, 'is_file');
        usort($arquivos, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
        $ultimo = $arquivos[0] ?? null;

        return [
            'configurado' => is_string($diretorio) && $diretorio !== '',
            'arquivo' => $ultimo === null ? null : basename($ultimo),
            'quando' => $ultimo === null ? null : now(config('gabaritu.fuso'))->setTimestamp((int) filemtime($ultimo))->format('Y-m-d H:i'),
            'idade_horas' => $ultimo === null ? null : (int) floor((time() - (int) filemtime($ultimo)) / 3600),
        ];
    }

    /** @return array{ultimo_tick: string|null, atrasado: bool} */
    private function scheduler(): array
    {
        $tick = Cache::get(self::CHAVE_DO_SCHEDULER);
        $momento = is_int($tick) ? now()->setTimestamp($tick) : null;

        return [
            'ultimo_tick' => $momento?->setTimezone(config('gabaritu.fuso'))->format('Y-m-d H:i:s'),
            'atrasado' => $momento === null || $momento->diffInMinutes(now()) >= self::SCHEDULER_ATRASADO_EM_MINUTOS,
        ];
    }

    private function versao(): string
    {
        $configurada = config('gabaritu.admin.versao');

        return is_string($configurada) && $configurada !== '' ? $configurada : 'desconhecida';
    }

    /**
     * Mesmos números de `gabaritu:inep --relatorio`.
     *
     * @return array<string, mixed>
     */
    private function inep(): array
    {
        $inep = fn () => Questao::query()->where('origem', Questao::ORIGEM_INEP);
        $porStatus = $inep()->selectRaw(
            "case when anulada then 'anulada' when alt_pendente then 'alt pendente' when assunto_id is null then 'sem classificação' "
            ."else 'classificada, jurídico: ' || coalesce(revisao_juridica, 'pendente') end as chave, count(*) as total",
        )->groupBy('chave')->orderBy('chave')->pluck('total', 'chave');

        return [
            'exibicao_ligada' => (bool) config('gabaritu.exibir_inep'),
            'total' => $inep()->count(),
            'por_ano' => $inep()->selectRaw("origem_ref->>'ano' as chave, count(*) as total")->groupBy('chave')->orderBy('chave')->pluck('total', 'chave')->map(fn ($t) => (int) $t)->all(),
            'por_status' => $porStatus->map(fn ($t) => (int) $t)->all(),
        ];
    }
}
