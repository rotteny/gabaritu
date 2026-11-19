<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import MateriaCartao from '@/components/aluno/MateriaCartao.vue';
import { agruparPorArea } from '@/lib/area';
import { rotas } from '@/lib/rotas';
import type { Fala, MateriaResumo } from '@/types/gabaritu';

const props = defineProps<{
    materias: MateriaResumo[];
    revisao_hoje_total: number;
    fala: Fala;
}>();

const grupos = computed(() => agruparPorArea(props.materias));
</script>

<template>
    <Head title="Início" />
    <h1 class="gb-titulo">Escolha uma matéria</h1>
    <FalaPeixoto :fala="fala" grande />

    <section
        v-if="revisao_hoje_total > 0"
        class="gb-cartao gb-revisao"
        aria-labelledby="revisao-titulo"
    >
        <div>
            <h2 id="revisao-titulo" class="gb-subtitulo">Revisão do dia</h2>
            <p class="gb-texto">
                {{ revisao_hoje_total }}
                {{
                    revisao_hoje_total === 1 ? 'questão está' : 'questões estão'
                }}
                esperando você. Revisar espaçado é o que faz a matéria ficar na
                cabeça.
            </p>
        </div>
        <Link :href="rotas.revisao" class="l-btn l-btn--orange"
            >Revisar agora</Link
        >
    </section>

    <section
        v-for="g in grupos"
        :key="g.area"
        class="gb-area"
        :aria-labelledby="`area-${g.cor}`"
    >
        <h2
            :id="`area-${g.cor}`"
            class="gb-area-titulo"
            :class="`gb-area-titulo--${g.cor}`"
        >
            {{ g.area }}
        </h2>
        <ul class="gb-materias">
            <li v-for="m in g.materias" :key="m.slug">
                <MateriaCartao :materia="m" />
            </li>
        </ul>
    </section>
</template>
