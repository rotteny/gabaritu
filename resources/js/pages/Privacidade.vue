<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabecalhoLegal from '@/components/CabecalhoLegal.vue';
import LinkExterno from '@/components/LinkExterno.vue';
import SecoesLegais from '@/components/SecoesLegais.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import { useAluno } from '@/composables/useAluno';
import { SECOES_PRIVACIDADE } from '@/data/privacidade';
import { rotas } from '@/lib/rotas';
import type { Fala } from '@/types/gabaritu';

defineProps<{ fala?: Fala | null }>();
const { gabaritu } = useAluno();
</script>

<template>
    <Head title="Privacidade" />
    <CabecalhoLegal
        titulo="Privacidade, sem enrolação"
        prefixo="p"
        :secoes="SECOES_PRIVACIDADE"
    />
    <SecoesLegais :secoes="SECOES_PRIVACIDADE" prefixo="p">
        <template #quem>
            <LinkExterno
                v-if="gabaritu"
                :href="gabaritu.repositorio_url"
                class="gb-link"
                >Ver o código no repositório</LinkExterno
            >
        </template>
        <template #contato>
            <p class="gb-texto">
                Escreva para
                <a
                    v-if="gabaritu"
                    :href="`mailto:${gabaritu.email_contato}`"
                    class="gb-link"
                    >{{ gabaritu.email_contato }}</a
                >. Leia também os
                <Link :href="rotas.termos" class="gb-link">Termos de uso</Link>.
            </p>
        </template>
    </SecoesLegais>
    <FalaPeixoto v-if="fala" :fala="fala" />
</template>
