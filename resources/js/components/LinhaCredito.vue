<script setup lang="ts">
import { Fish } from '@lucide/vue';
import LinkExterno from '@/components/LinkExterno.vue';

defineProps<{ repositorioUrl: string }>();
</script>

<template>
    <p class="credito">
        feito com <Fish class="credito-icone" aria-hidden="true" /> por
        <LinkExterno href="https://github.com/rotteny">rotteny</LinkExterno>
        ·
        <LinkExterno :href="repositorioUrl">código aberto</LinkExterno> no
        GitHub
    </p>
</template>
