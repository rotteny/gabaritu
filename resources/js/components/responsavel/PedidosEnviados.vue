<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { rotas } from '@/lib/rotas';
import { formatarData, rotuloParentesco } from '@/lib/responsavel';
import type { PedidoEnviado } from '@/types/gabaritu';

defineProps<{ pedidos: PedidoEnviado[] }>();

const cancelar = (id: number) =>
    router.delete(rotas.pedido(id), { preserveScroll: true });
</script>

<template>
    <section
        v-if="pedidos.length > 0"
        class="gb-cartao gb-form"
        aria-labelledby="pedidos-env"
    >
        <h2 id="pedidos-env" class="gb-subtitulo">Pedidos enviados</h2>
        <ul class="gb-lista-linhas">
            <li v-for="p in pedidos" :key="p.id">
                {{ p.email_mascarado }}
                ({{ rotuloParentesco(p.parentesco, p.parentesco_outro) }})
                <span class="gb-miudo"
                    >vale até {{ formatarData(p.expira_em) }}</span
                >
                <button
                    type="button"
                    class="l-btn l-btn--white gb-btn-mini"
                    :aria-label="`Cancelar o pedido para ${p.email_mascarado}`"
                    @click="cancelar(p.id)"
                >
                    Cancelar
                </button>
            </li>
        </ul>
    </section>
</template>
