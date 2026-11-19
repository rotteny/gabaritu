<script setup lang="ts">
import { computed } from 'vue';
import type { PontoEvolucao } from '@/types/gabaritu';

const props = defineProps<{ pontos: PontoEvolucao[]; titulo: string }>();

const L = 320;
const A = 140;
const MARGEM = 24;
const MIN = 300;
const MAX = 900;

const xy = computed(() =>
    props.pontos.map((p, i) => ({
        x:
            props.pontos.length === 1
                ? L / 2
                : MARGEM + (i * (L - MARGEM * 2)) / (props.pontos.length - 1),
        y: A - MARGEM - ((p.nota - MIN) / (MAX - MIN)) * (A - MARGEM * 2),
        ...p,
    })),
);
const linha = computed(() => xy.value.map((p) => `${p.x},${p.y}`).join(' '));
const formatarData = (d: string) =>
    new Date(`${d}T12:00:00`).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    });
</script>

<template>
    <figure class="gb-grafico">
        <svg
            :viewBox="`0 0 ${L} ${A}`"
            role="img"
            :aria-label="`Evolução da nota em ${titulo}`"
        >
            <line
                class="gb-grafico-base"
                :x1="MARGEM"
                :x2="L - MARGEM"
                :y1="A - MARGEM"
                :y2="A - MARGEM"
            />
            <polyline
                v-if="xy.length > 1"
                class="gb-grafico-linha"
                :points="linha"
            />
            <circle
                v-for="p in xy"
                :key="p.data"
                class="gb-grafico-ponto"
                :cx="p.x"
                :cy="p.y"
                r="6"
            />
        </svg>
        <figcaption class="gb-sr">
            <span v-for="p in pontos" :key="p.data"
                >{{ formatarData(p.data) }}: nota {{ p.nota }}.
            </span>
        </figcaption>
        <p class="gb-miudo gb-grafico-eixo">
            <span>{{ formatarData(pontos[0].data) }}</span>
            <span>{{ formatarData(pontos[pontos.length - 1].data) }}</span>
        </p>
    </figure>
</template>
