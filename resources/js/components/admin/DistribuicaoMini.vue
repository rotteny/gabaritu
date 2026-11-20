<script setup lang="ts">
import type { DistribuicaoItem } from '@/types/admin';

defineProps<{ itens: DistribuicaoItem[] }>();
</script>

<template>
    <span v-if="itens.length" class="ad-num">
        <span
            v-for="d in itens"
            :key="d.letra"
            class="ad-tag"
            :class="d.correta ? 'ad-tag--ok' : ''"
            :title="`${d.total} respostas`"
        >
            {{ d.letra }} {{ d.pct === null ? '-' : `${d.pct}%` }}
        </span>
    </span>
    <span v-else class="ad-mudo">-</span>
</template>
