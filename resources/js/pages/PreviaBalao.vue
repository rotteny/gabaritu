<script setup lang="ts">
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import type { Fala } from '@/types/gabaritu';

/** Só para a rota local /_previa/balao: balão normal e balão de grito lado a lado. */
defineProps<{ normal: Fala; grito: Fala }>();
</script>

<template>
    <div class="gb-pagina">
        <FalaPeixoto :fala="normal" grande />
        <FalaPeixoto :fala="grito" grande />
    </div>
</template>
