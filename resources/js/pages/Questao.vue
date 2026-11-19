<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TelaQuestao from '@/components/questao/TelaQuestao.vue';
import type { Fala, QuestaoDados, Tentativa } from '@/types/gabaritu';

defineProps<{
    tentativa: Tentativa;
    questao: QuestaoDados;
    fala: Fala;
}>();
</script>

<template>
    <Head :title="`${tentativa.materia.nome}: questão ${tentativa.posicao}`" />
    <!-- :key recria a tela a cada questão: estado, fala e efeitos começam do zero -->
    <TelaQuestao
        :key="questao.id"
        :tentativa="tentativa"
        :questao="questao"
        :fala="fala"
    />
</template>
