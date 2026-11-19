<script setup lang="ts">
import { Fish } from '@lucide/vue';

defineProps<{ repositorioUrl: string }>();
</script>

<template>
    <p class="credito">
        feito com <Fish class="credito-icone" aria-hidden="true" /> por
        <a href="https://github.com/rotteny" rel="noopener">rotteny</a>
        ·
        <a :href="repositorioUrl" rel="noopener">código aberto</a> no GitHub
    </p>
</template>
