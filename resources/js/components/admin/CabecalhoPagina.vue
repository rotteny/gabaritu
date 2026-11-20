<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ titulo: string; subtitulo?: string }>();
</script>

<template>
    <Head :title="`${titulo} · Admin`" />
    <div class="ad-cab">
        <div>
            <h1 class="ad-titulo">{{ titulo }}</h1>
            <p v-if="subtitulo" class="ad-sub">{{ subtitulo }}</p>
        </div>
        <div class="ad-filtros"><slot /></div>
    </div>
</template>
