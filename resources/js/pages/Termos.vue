<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabecalhoLegal from '@/components/CabecalhoLegal.vue';
import SecoesLegais from '@/components/SecoesLegais.vue';
import { useAluno } from '@/composables/useAluno';
import { SECOES_TERMOS } from '@/data/termos';
import { rotas } from '@/lib/rotas';

const { gabaritu } = useAluno();
const secoes = [...SECOES_TERMOS, { id: 'contato', titulo: 'Contato' }];
</script>

<template>
    <Head title="Termos de uso" />
    <CabecalhoLegal titulo="Termos de uso" prefixo="t" :secoes="secoes" />
    <SecoesLegais :secoes="secoes" prefixo="t">
        <template #contato>
            <p class="gb-texto">
                Dúvidas, pedidos ou problemas: escreva para
                <a
                    v-if="gabaritu"
                    :href="`mailto:${gabaritu.email_contato}`"
                    class="gb-link"
                    >{{ gabaritu.email_contato }}</a
                >. Veja também a
                <Link :href="rotas.privacidade" class="gb-link"
                    >Política de Privacidade</Link
                >.
            </p>
        </template>
    </SecoesLegais>
</template>
