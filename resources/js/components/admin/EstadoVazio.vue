<script setup lang="ts">
defineProps<{ titulo: string }>();
</script>

<template>
    <div class="ad-vazio">
        <strong>{{ titulo }}</strong>
        <slot />
    </div>
</template>
