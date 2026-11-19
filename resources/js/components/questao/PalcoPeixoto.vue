<script setup lang="ts">
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { INCHACO, humorPeixoto } from '@/lib/humor';
import type { HumorFala } from '@/types/gabaritu';

defineProps<{
    humor: HumorFala;
    textoCompleto: string;
    digitado: string;
    reacao: 'acerto' | 'erro' | null;
    podePular: boolean;
}>();
defineEmits<{ pular: [] }>();
</script>

<template>
    <section class="q-palco" aria-label="Peixoto, o baiacu">
        <div class="q-balao" :data-humor="humor">
            <span class="q-balao-corpo" aria-hidden="true">
                <span class="q-fantasma">{{ textoCompleto }}</span>
                <span class="q-digitado">{{ digitado }}</span>
            </span>
            <span class="gb-sr" role="status" aria-live="polite">{{
                textoCompleto
            }}</span>
            <button
                v-if="podePular"
                type="button"
                class="q-pular"
                @click="$emit('pular')"
            >
                Pular animação
            </button>
        </div>
        <div class="q-peixe" aria-hidden="true">
            <div class="q-flutua">
                <div class="q-infla" :style="{ '--puff': INCHACO[humor] }">
                    <div class="q-ato" :class="reacao && `q-ato--${reacao}`">
                        <PeixotoSvg :humor="humorPeixoto(humor)" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
