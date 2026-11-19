<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';

defineProps<{ href: string }>();
</script>

<template>
    <a
        :href="href"
        class="link-externo"
        target="_blank"
        rel="noopener noreferrer"
        ><slot /><ExternalLink
            class="link-externo-icone"
            aria-hidden="true"
        /><span class="sr-only"> (abre em nova aba)</span></a
    >
</template>
