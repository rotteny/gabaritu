<script setup lang="ts">
import type { StatusAluno } from '@/types/gabaritu';

defineProps<{ status: StatusAluno }>();
</script>

<template>
    <span
        class="gb-tag"
        :class="status === 'ativo' ? 'gb-tag--verde' : 'gb-tag--cinza'"
    >
        {{ status === 'ativo' ? 'Ativo' : 'Aguardando autorização' }}
    </span>
</template>
