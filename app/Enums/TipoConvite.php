<?php

namespace App\Enums;

enum TipoConvite: string
{
    case Autorizacao = 'autorizacao';
    case Coresponsavel = 'coresponsavel';
    case CoresponsavelAluno = 'coresponsavel_aluno';
    case Transferencia = 'transferencia';
    case PedidoResponsavel = 'pedido_responsavel';
    case PedidoVinculo = 'pedido_vinculo';

    /** Pedidos iniciados pelo responsável: o destinatário é o aluno. */
    public function ehPedidoDoResponsavel(): bool
    {
        return $this === self::PedidoResponsavel || $this === self::PedidoVinculo;
    }

    /** @return list<string> */
    public static function pedidos(): array
    {
        return [self::PedidoResponsavel->value, self::PedidoVinculo->value];
    }

    /** @return list<string> tipos em que o destinatário é o responsável */
    public static function paraResponsavel(): array
    {
        return array_values(array_diff(array_map(fn (self $t) => $t->value, self::cases()), self::pedidos()));
    }

    /** Convites que acrescentam um responsável (a transferência só troca um por outro). */
    public function acrescentaResponsavel(): bool
    {
        return $this !== self::Transferencia && ! $this->ehPedidoDoResponsavel();
    }
}
