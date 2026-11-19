<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DesempenhoBloco from '@/components/aluno/DesempenhoBloco.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import type { DesempenhoMateria, Fala } from '@/types/gabaritu';

defineProps<{ materias: DesempenhoMateria[]; fala: Fala }>();
</script>

<template>
    <Head title="Desempenho" />
    <h1 class="gb-titulo">Seu desempenho</h1>
    <FalaPeixoto :fala="fala" />
    <p class="gb-aviso gb-aviso--info">
        Só você vê isso. As notas são estimativas do Gabaritu, não a nota
        oficial do ENEM.
    </p>
    <p v-if="materias.length === 0" class="gb-aviso">
        Você ainda não respondeu nada. Escolha uma matéria no início pra
        começar.
    </p>
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
