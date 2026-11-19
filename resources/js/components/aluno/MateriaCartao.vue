<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatarNota } from '@/lib/area';
import { rotas } from '@/lib/rotas';
import type { MateriaResumo } from '@/types/gabaritu';

defineProps<{ materia: MateriaResumo }>();
</script>

<template>
    <Link :href="rotas.materia(materia.slug)" class="gb-materia">
        <span class="gb-materia-nome">{{ materia.nome }}</span>
        <span class="gb-materia-info">
            {{ materia.assuntos_total }}
            {{ materia.assuntos_total === 1 ? 'assunto' : 'assuntos' }}
        </span>
        <span v-if="materia.nota !== null" class="gb-nota">
            {{ formatarNota(materia.nota) }}
            <span class="gb-miudo">nota estimada</span>
        </span>
        <span v-else class="gb-miudo">Sem nota ainda</span>
        <span v-if="materia.revisao_hoje > 0" class="gb-tag gb-tag--roxo">
            {{ materia.revisao_hoje }} pra revisar hoje
        </span>
    </Link>
</template>
