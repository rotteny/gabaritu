<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Periodo } from '@/types/admin';

defineProps<{ periodo: Periodo; base: string }>();

const opcoes: { valor: Periodo; rotulo: string }[] = [
    { valor: '30', rotulo: '30 dias' },
    { valor: '90', rotulo: '90 dias' },
    { valor: 'tudo', rotulo: 'Tudo' },
];
</script>

<template>
    <nav class="ad-seg" aria-label="Período">
        <Link
            v-for="o in opcoes"
            :key="o.valor"
            :href="`${base}?periodo=${o.valor}`"
            :aria-current="periodo === o.valor ? 'true' : undefined"
            preserve-scroll
            >{{ o.rotulo }}</Link
        >
    </nav>
</template>
