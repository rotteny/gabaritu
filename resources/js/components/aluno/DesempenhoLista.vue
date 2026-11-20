<script setup lang="ts">
import DesempenhoBloco from '@/components/aluno/DesempenhoBloco.vue';
import type { DesempenhoMateria } from '@/types/gabaritu';

defineProps<{ materias: DesempenhoMateria[]; vazio: string }>();
</script>

<template>
    <p v-if="materias.length === 0" class="gb-aviso">{{ vazio }}</p>
    <section v-for="m in materias" :key="m.slug" class="gb-cartao">
        <DesempenhoBloco :item="m" :nivel="2" />
        <details v-if="m.assuntos.length > 0" class="gb-detalhes">
            <summary>Ver por assunto ({{ m.assuntos.length }})</summary>
            <DesempenhoBloco
                v-for="a in m.assuntos"
                :key="a.slug"
                :item="a"
                :nivel="3"
            />
        </details>
    </section>
</template>
