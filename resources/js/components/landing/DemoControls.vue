<script setup lang="ts">
import { RotateCcw, Volume2, VolumeX } from '@lucide/vue';
import { niveisZoeira } from '@/data/landingDemo';
import type { Zoeira } from '@/data/landingDemo';

defineProps<{ somLigado: boolean }>();
defineEmits<{ alternarSom: []; reiniciar: [] }>();

const nivel = defineModel<Zoeira>('nivel', { required: true });
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <fieldset class="m-0 min-w-0 border-0 p-0">
            <legend class="l-label">Nível de zoeira</legend>
            <div class="flex flex-wrap gap-2">
                <label v-for="n in niveisZoeira" :key="n.id" class="l-pill">
                    <input
                        v-model="nivel"
                        type="radio"
                        name="nivel-demo"
                        :value="n.id"
                        class="sr-only"
                    />
                    <span>{{ n.nome }}</span>
                </label>
            </div>
        </fieldset>
        <div class="flex gap-2">
            <button
                type="button"
                class="l-icon-btn"
                :aria-pressed="somLigado"
                @click="$emit('alternarSom')"
            >
                <Volume2 v-if="somLigado" class="size-5" aria-hidden="true" />
                <VolumeX v-else class="size-5" aria-hidden="true" />
                <span class="sr-only">Efeitos sonoros</span>
            </button>
            <button
                type="button"
                class="l-icon-btn"
                @click="$emit('reiniciar')"
            >
                <RotateCcw class="size-5" aria-hidden="true" />
                <span class="sr-only">Recomeçar a questão</span>
            </button>
        </div>
    </div>
</template>
