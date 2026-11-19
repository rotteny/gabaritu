<script setup lang="ts">
withDefaults(
    defineProps<{ tamanho?: 'sm' | 'md' | 'hero'; comPeixe?: boolean }>(),
    { tamanho: 'md', comPeixe: false },
);

const letras = Array.from('Gabaritu');
</script>

<template>
    <span class="marca" :class="`marca--${tamanho}`">
        <span class="marca-sr">Gabaritu</span>
        <span class="marca-letras" aria-hidden="true">
            <span v-for="(letra, i) in letras" :key="i" class="marca-letra">{{
                letra
            }}</span>
        </span>
        <span v-if="comPeixe" class="marca-bolhas" aria-hidden="true">
            <i /><i /><i />
        </span>
    </span>
</template>
