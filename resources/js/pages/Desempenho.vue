<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DesempenhoLista from '@/components/aluno/DesempenhoLista.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import type { DesempenhoMateria, Fala } from '@/types/gabaritu';

defineProps<{ materias: DesempenhoMateria[]; fala: Fala }>();
</script>

<template>
    <Head title="Desempenho" />
    <h1 class="gb-titulo">Seu desempenho</h1>
    <FalaPeixoto :fala="fala" />
    <p class="gb-aviso gb-aviso--info">
        Só você e quem acompanha a sua conta (seu responsável) veem isso. As
        notas são estimativas do Gabaritu, não a nota oficial do ENEM.
    </p>
    <DesempenhoLista
        :materias="materias"
        vazio="Você ainda não respondeu nada. Escolha uma matéria no início pra começar."
    />
</template>
