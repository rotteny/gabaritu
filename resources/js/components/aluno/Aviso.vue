<script setup lang="ts">
withDefaults(defineProps<{ tipo?: 'info' | 'erro' | 'ok' }>(), {
    tipo: 'info',
});
</script>

<template>
    <p
        class="gb-aviso"
        :class="`gb-aviso--${tipo}`"
        :role="tipo === 'erro' ? 'alert' : 'status'"
    >
        <slot />
    </p>
</template>
