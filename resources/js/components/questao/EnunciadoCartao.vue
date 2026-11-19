<script setup lang="ts">
defineProps<{
    contexto: string | null;
    enunciado: string;
    compacto: boolean;
}>();
</script>

<template>
    <section
        class="q-cartao"
        :class="{ 'q-cartao--compacto': compacto }"
        aria-label="Pergunta"
    >
        <p v-if="contexto && !compacto" class="q-contexto">{{ contexto }}</p>
        <p class="q-enunciado">{{ enunciado }}</p>
    </section>
</template>
