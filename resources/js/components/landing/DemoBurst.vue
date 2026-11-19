<script setup lang="ts">
const PONTAS = 16;
const explosao = Array.from({ length: PONTAS * 2 }, (_, i) => {
    const r = i % 2 ? 64 : 96;
    const a = (Math.PI / PONTAS) * i;

    return `${(Math.cos(a) * r).toFixed(1)},${(Math.sin(a) * r).toFixed(1)}`;
}).join(' ');

const cores = ['yellow', 'orange', 'cyan', 'purple', 'slime', 'pink'];
const confetes = Array.from({ length: 18 }, (_, i) => {
    const a = (i / 18) * Math.PI * 2;
    const raio = 120 + (i % 3) * 40;

    return {
        dx: `${Math.round(Math.cos(a) * raio)}px`,
        dy: `${Math.round(Math.sin(a) * raio)}px`,
        cor: `var(--${cores[i % cores.length]})`,
    };
});
</script>

<template>
    <div class="l-burst" aria-hidden="true">
        <svg class="l-burst-star" viewBox="-100 -100 200 200">
            <polygon :points="explosao" />
        </svg>
        <span class="l-burst-word">ACERTOU!</span>
        <span
            v-for="(c, i) in confetes"
            :key="i"
            class="l-confete"
            :style="{ '--dx': c.dx, '--dy': c.dy, '--c': c.cor }"
        />
    </div>
</template>
