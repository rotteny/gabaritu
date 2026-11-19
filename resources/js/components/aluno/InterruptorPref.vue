<script setup lang="ts">
defineProps<{ rotulo: string; ajuda: string }>();
const ligado = defineModel<boolean>({ required: true });
</script>

<template>
    <label class="gb-interruptor">
        <input v-model="ligado" type="checkbox" role="switch" />
        <span class="gb-interruptor-trilho" aria-hidden="true"><i /></span>
        <span class="gb-interruptor-texto">
            <span class="gb-legenda">{{ rotulo }}</span>
            <span class="gb-ajuda">{{ ajuda }}</span>
        </span>
    </label>
</template>
