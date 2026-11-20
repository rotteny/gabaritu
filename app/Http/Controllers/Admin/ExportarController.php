<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\AcaoDeAuditoria;
use App\Domain\Admin\Auditoria;
use App\Domain\Admin\ExportadorDeCsv;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exporta CSV agregado. Cada download vira uma linha na auditoria. */
class ExportarController extends Controller
{
    public function __invoke(string $tipo, ExportadorDeCsv $exportador, Auditoria $auditoria): StreamedResponse
    {
        abort_unless(in_array($tipo, ExportadorDeCsv::TIPOS, true), 404);

        $admin = Auth::guard('admin')->user();
        assert($admin instanceof Admin);
        $auditoria->registrar($admin, AcaoDeAuditoria::ExportarCsv, detalhes: ['tipo' => $tipo]);

        $csv = $exportador->montar($tipo);
        $nome = "gabaritu-{$tipo}-".now(config('gabaritu.fuso'))->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($csv): void {
            $saida = fopen('php://output', 'w') ?: throw new \RuntimeException('Sem saída para o CSV.');
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, $csv['cabecalho'], ',', '"', '');
            foreach ($csv['linhas'] as $linha) {
                fputcsv($saida, array_map(ExportadorDeCsv::proteger(...), $linha), ',', '"', '');
            }
            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
