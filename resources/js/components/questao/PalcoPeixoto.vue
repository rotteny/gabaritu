<script setup lang="ts">
import { computed } from 'vue';
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { ehGrito } from '@/lib/grito';
import { INCHACO, humorPeixoto } from '@/lib/humor';
import type { HumorFala } from '@/types/gabaritu';

const props = defineProps<{
    humor: HumorFala;
    textoCompleto: string;
    digitado: string;
    reacao: 'acerto' | 'erro' | null;
    podePular: boolean;
}>();
defineEmits<{ pular: [] }>();
const grito = computed(() => ehGrito(props.textoCompleto));
</script>

<template>
    <section class="q-palco" aria-label="Peixoto, o baiacu">
        <div
            class="q-balao gb-grito-alvo"
            :data-humor="humor"
            :data-grito="grito"
        >
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
