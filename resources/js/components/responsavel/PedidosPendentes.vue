<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { rotas } from '@/lib/rotas';
import { formatarData, nomeDoVinculo } from '@/lib/responsavel';
import type { PedidoPendente } from '@/types/gabaritu';

defineProps<{ apelido: string; pedidos: PedidoPendente[] }>();

const resolver = (url: string) =>
    router.post(url, {}, { preserveScroll: true });
</script>

<template>
    <div v-if="pedidos.length > 0" class="gb-bloco">
        <h3>Alguém quer acompanhar {{ apelido }}</h3>
        <div v-for="p in pedidos" :key="p.id" class="gb-aviso gb-aviso--info">
            <p class="gb-texto">
                <strong>{{ p.solicitante.nome }}</strong>
                ({{ nomeDoVinculo(p.solicitante) }},
                {{ p.solicitante.email_mascarado }}) pediu para ser responsável
                por {{ apelido }}. Você só aprova se conhece essa pessoa.
            </p>
            <p class="gb-miudo">
                {{
                    p.aluno_aceitou
                        ? `${apelido} já aceitou.`
                        : `${apelido} ainda não respondeu.`
                }}
                O pedido vale até {{ formatarData(p.expira_em) }}.
            </p>
            <div class="gb-acoes-aluno">
                <button
                    type="button"
                    class="l-btn l-btn--orange gb-btn-mini"
                    @click="resolver(rotas.aprovarPedido(p.id))"
                >
                    Aprovar
                </button>
                <button
                    type="button"
                    class="l-btn l-btn--white gb-btn-mini"
                    @click="resolver(rotas.recusarPedidoResponsavel(p.id))"
                >
                    Recusar
                </button>
            </div>
        </div>
    </div>
</template>
