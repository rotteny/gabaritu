<script setup lang="ts">
defineProps<{ tipo: 'acerto' | 'erro' }>();

const PONTAS = 14;
const estrela = Array.from({ length: PONTAS * 2 }, (_, i) => {
    const r = i % 2 ? 62 : 96;
    const a = (Math.PI / PONTAS) * i;

    return `${(Math.cos(a) * r).toFixed(1)},${(Math.sin(a) * r).toFixed(1)}`;
}).join(' ');

const CORES = ['yellow', 'orange', 'cyan', 'purple', 'slime', 'pink'];
const confetes = Array.from({ length: 16 }, (_, i) => {
    const a = (i / 16) * Math.PI * 2;
    const raio = 90 + (i % 3) * 36;

    return {
        '--dx': `${Math.round(Math.cos(a) * raio)}px`,
        '--dy': `${Math.round(Math.sin(a) * raio)}px`,
        '--c': `var(--${CORES[i % CORES.length]})`,
    };
});
</script>

<template>
    <span
        class="l-burst q-explosao"
        :class="`q-explosao--${tipo}`"
        aria-hidden="true"
    >
        <svg class="l-burst-star" viewBox="-100 -100 200 200">
            <polygon :points="estrela" />
        </svg>
        <template v-if="tipo === 'acerto'">
            <i
                v-for="(c, i) in confetes"
                :key="i"
                class="l-confete"
                :style="c"
            />
        </template>
    </span>
</template>
