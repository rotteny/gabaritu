<?php

namespace App\Jobs;

use App\Domain\Responsavel\DadosDeParentesco;
use App\Domain\Responsavel\PedidoDeVinculo;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;

/** Cria o pedido do responsável e envia os e-mails depois da resposta HTTP (sem depender de worker de fila). */
class ProcessarPedidoDeVinculo
{
    use Dispatchable;

    public function __construct(
        private readonly int $solicitanteId,
        private readonly string $email,
        private readonly string $parentesco,
        private readonly ?string $parentescoOutro,
        private readonly ?string $ip,
    ) {}

    public function handle(PedidoDeVinculo $pedidos): void
    {
        $solicitante = User::query()->find($this->solicitanteId);

        if ($solicitante === null) {
            return;
        }

        $pedidos->processar($solicitante, $this->email, DadosDeParentesco::de($this->parentesco, $this->parentescoOutro), $this->ip);
    }
}
