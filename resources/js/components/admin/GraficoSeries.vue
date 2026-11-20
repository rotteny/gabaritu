<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';

type Serie = { chave: string; rotulo: string; cor: string };
type Linha = { dia: string } & Record<string, number | string>;

const props = withDefaults(
    defineProps<{
        titulo: string;
        dados: Linha[];
        series: Serie[];
        tipo?: 'linha' | 'barra';
        altura?: number;
    }>(),
    { tipo: 'linha', altura: 220 },
);

const caixa = useTemplateRef<HTMLDivElement>('caixa');
const { width } = useElementSize(caixa);
const largura = computed(() => Math.max(width.value, 280));
const foco = ref<number | null>(null);

const margem = { topo: 10, direita: 10, base: 24, esquerda: 38 };
const areaW = computed(() => largura.value - margem.esquerda - margem.direita);
const areaH = computed(() => props.altura - margem.topo - margem.base);

const valor = (l: Linha, chave: string) => Number(l[chave] ?? 0);
const maximoBruto = computed(() =>
    Math.max(
        0,
        ...props.dados.flatMap((l) =>
            props.series.map((s) => valor(l, s.chave)),
        ),
    ),
);
const vazio = computed(() => maximoBruto.value === 0);

/** Teto "redondo" (1, 2, 5 x 10^n) para eixo legível. */
const teto = computed(() => {
    const m = Math.max(maximoBruto.value, 4);
    const potencia = 10 ** Math.floor(Math.log10(m));

    for (const f of [1, 2, 2.5, 5, 10]) {
        if (m <= f * potencia) {
            return f * potencia;
        }
    }

    return 10 * potencia;
});
const marcasY = computed(() =>
    [0, 1, 2, 3, 4].map((i) => (teto.value / 4) * i),
);

const passo = computed(() => areaW.value / Math.max(props.dados.length, 1));
const xDe = (i: number) =>
    props.tipo === 'barra'
        ? margem.esquerda + passo.value * i + passo.value / 2
        : margem.esquerda +
          (props.dados.length > 1
              ? (areaW.value / (props.dados.length - 1)) * i
              : areaW.value / 2);
const yDe = (v: number) =>
    margem.topo + areaH.value - (v / teto.value) * areaH.value;

const caminhos = computed(() =>
    props.series.map((s) => ({
        ...s,
        d: props.dados
            .map(
                (l, i) =>
                    `${i === 0 ? 'M' : 'L'}${xDe(i).toFixed(1)},${yDe(valor(l, s.chave)).toFixed(1)}`,
            )
            .join(' '),
    })),
);

const larguraBarra = computed(() =>
    Math.max(1, Math.min(passo.value * 0.7, 24)),
);

const marcasX = computed(() => {
    const n = props.dados.length;
    const quantas = Math.min(n, largura.value < 420 ? 4 : 7);

    if (n === 0) {
        return [];
    }

    return Array.from(
        new Set(
            Array.from({ length: quantas }, (_, k) =>
                Math.round((k * (n - 1)) / Math.max(quantas - 1, 1)),
            ),
        ),
    );
});

const fmtDia = (d: string) => `${d.slice(8, 10)}/${d.slice(5, 7)}`;
const fmtNum = (n: number) => n.toLocaleString('pt-BR');

const leitura = computed(() => {
    if (foco.value === null || !props.dados[foco.value]) {
        return '';
    }

    const l = props.dados[foco.value];

    return `${fmtDia(l.dia)}: ${props.series.map((s) => `${s.rotulo} ${fmtNum(valor(l, s.chave))}`).join(' · ')}`;
});

function indiceDoPonteiro(e: PointerEvent) {
    const el = e.currentTarget as SVGElement;
    const x = e.clientX - el.getBoundingClientRect().left;
    const n = props.dados.length;

    if (n === 0) {
        return;
    }

    const idx =
        props.tipo === 'barra'
            ? Math.floor((x - margem.esquerda) / passo.value)
            : Math.round(
                  ((x - margem.esquerda) / Math.max(areaW.value, 1)) * (n - 1),
              );
    foco.value = Math.min(n - 1, Math.max(0, idx));
}

function aoTeclar(e: KeyboardEvent) {
    const n = props.dados.length;
    const atual = foco.value ?? -1;
    const mapa: Record<string, number> = {
        ArrowRight: Math.min(n - 1, atual + 1),
        ArrowLeft: Math.max(0, atual === -1 ? 0 : atual - 1),
        Home: 0,
        End: n - 1,
    };

    if (e.key in mapa && n > 0) {
        e.preventDefault();
        foco.value = mapa[e.key];
    } else if (e.key === 'Escape') {
        foco.value = null;
    }
}
</script>

<template>
    <figure class="ad-graf" ref="caixa">
        <svg
            :viewBox="`0 0 ${largura} ${altura}`"
            :height="altura"
            role="application"
            tabindex="0"
            :aria-label="`${titulo}. Use as setas para ler cada dia.`"
            @pointermove="indiceDoPonteiro"
            @pointerleave="foco = null"
            @keydown="aoTeclar"
        >
            <g class="ad-graf-eixo">
                <g v-for="m in marcasY" :key="m">
                    <line
                        class="ad-graf-grade"
                        :x1="margem.esquerda"
                        :x2="largura - margem.direita"
                        :y1="yDe(m)"
                        :y2="yDe(m)"
                    />
                    <text
                        :x="margem.esquerda - 6"
                        :y="yDe(m) + 4"
                        text-anchor="end"
                    >
                        {{ fmtNum(m) }}
                    </text>
                </g>
                <text
                    v-for="i in marcasX"
                    :key="i"
                    :x="xDe(i)"
                    :y="altura - 6"
                    :text-anchor="
                        i === 0
                            ? 'start'
                            : i === dados.length - 1
                              ? 'end'
                              : 'middle'
                    "
                >
                    {{ fmtDia(dados[i].dia) }}
                </text>
            </g>
            <template v-if="tipo === 'barra'">
                <template v-for="s in series" :key="s.chave">
                    <rect
                        v-for="(l, i) in dados"
                        :key="l.dia"
                        :x="xDe(i) - larguraBarra / 2"
                        :y="yDe(valor(l, s.chave))"
                        :width="larguraBarra"
                        :height="Math.max(0, yDe(0) - yDe(valor(l, s.chave)))"
                        :fill="s.cor"
                        :opacity="foco === null || foco === i ? 1 : 0.55"
                    />
                </template>
            </template>
            <template v-else>
                <path
                    v-for="c in caminhos"
                    :key="c.chave"
                    :d="c.d"
                    fill="none"
                    :stroke="c.cor"
                    stroke-width="2"
                    stroke-linejoin="round"
                />
            </template>
            <template v-if="foco !== null && dados[foco]">
                <line
                    v-if="tipo === 'linha'"
                    class="ad-graf-foco"
                    :x1="xDe(foco)"
                    :x2="xDe(foco)"
                    :y1="margem.topo"
                    :y2="margem.topo + areaH"
                />
                <template v-if="tipo === 'linha'">
                    <circle
                        v-for="s in series"
                        :key="s.chave"
                        :cx="xDe(foco)"
                        :cy="yDe(valor(dados[foco], s.chave))"
                        r="4"
                        :fill="s.cor"
                        stroke="#fff"
                        stroke-width="2"
                    />
                </template>
            </template>
            <text
                v-if="vazio"
                :x="largura / 2"
                :y="altura / 2"
                text-anchor="middle"
                class="ad-graf-eixo"
                fill="#5b5470"
                font-size="13"
            >
                Ainda sem dados neste período
            </text>
        </svg>
        <ul class="ad-legenda">
            <li v-for="s in series" :key="s.chave">
                <i :style="{ background: s.cor }" />{{ s.rotulo }}
            </li>
        </ul>
        <p class="ad-dica-graf" aria-live="polite" role="status">
            <template v-if="leitura">{{ leitura }}</template>
            <span v-else class="ad-mudo"
                >Passe o mouse ou use as setas no gráfico para ver os
                valores.</span
            >
        </p>
    </figure>
</template>
