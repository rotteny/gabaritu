<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { rotas } from '@/lib/rotas';
import { formatarData, nomeDoVinculo } from '@/lib/responsavel';
import type { PedidoDeVinculo } from '@/types/gabaritu';

defineProps<{ pedidos: PedidoDeVinculo[] }>();

const resolver = (url: string) =>
    router.post(url, {}, { preserveScroll: true });
</script>

<template>
    <div v-if="pedidos.length > 0" class="gb-bloco">
        <h3>Pedidos para ser seu responsável</h3>
        <div v-for="p in pedidos" :key="p.id" class="gb-aviso gb-aviso--info">
            <p class="gb-texto">
                <strong>{{ p.solicitante.nome }}</strong>
                ({{ nomeDoVinculo(p.solicitante) }},
                {{ p.solicitante.email_mascarado }}) quer ser seu responsável no
                Gabaritu. Aceite só se você conhece essa pessoa.
            </p>
            <p class="gb-miudo">
                O pedido vale até {{ formatarData(p.expira_em) }}.
                <template v-if="p.aceito_por_mim && p.aguarda_responsavel">
                    Você aceitou: falta um dos seus responsáveis aprovar.
                </template>
                <template v-else-if="p.aguarda_responsavel">
                    Também precisa da aprovação de um dos seus responsáveis.
                </template>
            </p>
            <div v-if="!p.aceito_por_mim" class="gb-acoes-aluno">
                <button
                    type="button"
                    class="l-btn l-btn--orange gb-btn-mini"
                    @click="resolver(rotas.aceitarPedido(p.id))"
                >
                    Aceitar
                </button>
                <button
                    type="button"
                    class="l-btn l-btn--white gb-btn-mini"
                    @click="resolver(rotas.recusarPedido(p.id))"
                >
                    Recusar
                </button>
            </div>
            <div v-else class="gb-acoes-aluno">
                <button
                    type="button"
                    class="l-btn l-btn--white gb-btn-mini"
                    @click="resolver(rotas.recusarPedido(p.id))"
                >
                    Desistir
                </button>
            </div>
        </div>
    </div>
</template>
