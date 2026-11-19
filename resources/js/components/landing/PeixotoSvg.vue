<script setup lang="ts">
import type { HumorPeixoto } from '@/composables/usePeixotoDemo';

withDefaults(defineProps<{ humor?: HumorPeixoto }>(), { humor: 'idle' });

const STAR = [
    [12, 1.5],
    [15.2, 8.4],
    [22.5, 9.2],
    [17, 14.2],
    [18.6, 21.6],
    [12, 17.8],
    [5.4, 21.6],
    [7, 14.2],
    [1.5, 9.2],
    [8.8, 8.4],
];

function estrela(x: number, y: number, tamanho: number): string {
    const k = tamanho / 24;

    return STAR.map(
        ([px, py]) => `${(x + px * k).toFixed(1)},${(y + py * k).toFixed(1)}`,
    ).join(' ');
}

const ESPINHOS = 14;
const espinhos = Array.from({ length: ESPINHOS }, (_, i) => {
    const a = (i / ESPINHOS) * Math.PI * 2 + 0.2;
    const ponto = (t: number, r: number) =>
        `${(100 + 53 * r * Math.cos(t)).toFixed(1)},${(85 + 46 * r * Math.sin(t)).toFixed(1)}`;

    return `${ponto(a - 0.15, 1)} ${ponto(a, 1.36)} ${ponto(a + 0.15, 1)}`;
});
</script>

<template>
    <svg
        class="pei"
        :data-humor="humor"
        viewBox="0 0 200 170"
        role="img"
        aria-label="Peixoto, o baiacu laranja"
    >
        <g class="pei-tail"><path d="M46 85 L10 58 Q0 85 10 112 Z" /></g>
        <g class="pei-spines">
            <polygon v-for="p in espinhos" :key="p" :points="p" />
        </g>
        <ellipse class="pei-body" cx="100" cy="85" rx="58" ry="50" />
        <ellipse class="pei-belly" cx="108" cy="118" rx="36" ry="16" />
        <circle class="pei-spot" cx="70" cy="52" r="6" />
        <circle class="pei-spot" cx="94" cy="42" r="5" />
        <circle class="pei-spot" cx="62" cy="76" r="4" />
        <g class="pei-fin">
            <path d="M94 96 Q70 92 64 114 Q84 120 98 108 Z" />
        </g>
        <g class="pei-cheeks">
            <circle cx="112" cy="94" r="7" />
            <circle cx="82" cy="98" r="5" />
        </g>
        <g class="pei-eye">
            <circle cx="124" cy="66" r="18" />
            <circle class="pei-pupil" cx="130" cy="70" r="8" />
            <circle class="pei-shine" cx="133" cy="66" r="3" />
        </g>
        <g class="pei-eye">
            <circle cx="90" cy="72" r="12" />
            <circle class="pei-pupil" cx="85" cy="68" r="5" />
            <circle class="pei-shine" cx="83" cy="66" r="2" />
        </g>
        <polygon class="pei-star" :points="estrela(108, 50, 32)" />
        <polygon class="pei-star" :points="estrela(78, 60, 24)" />
        <g class="pei-brows">
            <path d="M104 42 L144 56" />
            <path d="M70 56 L104 64" />
        </g>
        <g class="pei-mouth pei-m-idle">
            <rect
                class="pei-tooth"
                x="136"
                y="108"
                width="9"
                height="11"
                rx="2"
            />
            <path d="M122 104 Q138 116 154 100" />
        </g>
        <g class="pei-mouth pei-m-mad">
            <path d="M120 114 L127 101 L135 114 L143 101 L152 112" />
        </g>
        <g class="pei-mouth pei-m-happy">
            <path d="M120 98 Q138 134 158 98 Z" />
            <ellipse class="pei-tongue" cx="139" cy="116" rx="8" ry="5" />
        </g>
    </svg>
</template>
