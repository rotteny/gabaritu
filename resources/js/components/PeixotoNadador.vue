<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { useNadadorScroll } from '@/composables/useNadadorScroll';
import { blipHover, palcoAtivo } from '@/lib/motorSom';

const props = defineProps<{ discreto?: boolean; reduzido?: boolean }>();

type Bolha = { id: number; dx: number };

const corpo = useTemplateRef<HTMLElement>('corpo');
const bolhas = ref<Bolha[]>([]);
const inchado = ref(false);
let proximo = 0;
let ultimaBolha = 0;

function soltarBolha() {
    const id = proximo++;
    bolhas.value.push({ id, dx: Math.round(Math.random() * 24 - 12) });
    window.setTimeout(() => {
        bolhas.value = bolhas.value.filter((b) => b.id !== id);
    }, 1400);
}

useNadadorScroll(
    {
        corpo,
        cauda: () => corpo.value?.querySelector('.pei-tail') ?? null,
        aoVelocidade: (v) => {
            const agora = performance.now();

            if (v > 900 && agora - ultimaBolha > 220) {
                ultimaBolha = agora;
                soltarBolha();
            }
        },
    },
    () => props.reduzido === true,
);

function cutucar() {
    soltarBolha();
    inchado.value = true;
    window.setTimeout(() => (inchado.value = false), 600);

    if (palcoAtivo()) {
        blipHover();
    }
}
</script>

<template>
    <div
        class="nadador"
        :class="{
            'nadador--reduzido': reduzido,
            'nadador--discreto': discreto,
        }"
    >
        <span
            v-for="b in bolhas"
            :key="b.id"
            class="nadador-bolha"
            :style="{ '--dx': `${b.dx}px` }"
            aria-hidden="true"
        />
        <button
            type="button"
            class="nadador-btn"
            aria-label="Cutucar o Peixoto"
            @click="cutucar"
        >
            <span ref="corpo" class="nadador-corpo">
                <span class="nadador-boia" :class="{ 'is-inchado': inchado }">
                    <PeixotoSvg />
                </span>
            </span>
        </button>
    </div>
</template>
