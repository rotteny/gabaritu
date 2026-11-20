<script setup lang="ts">
import { formatarNota } from '@/lib/area';
import type { NotaResumo } from '@/types/gabaritu';

defineProps<{ notas: NotaResumo[] }>();
</script>

<template>
    <div class="gb-bloco">
        <h3>Nota estimada por matéria</h3>
        <ul class="gb-notas">
            <li v-for="n in notas" :key="n.slug">
                <span class="gb-miudo">{{ n.nome }}</span>
                <strong>{{
                    n.nota === null ? 'sem nota' : formatarNota(n.nota)
                }}</strong>
            </li>
        </ul>
    </div>
</template>
