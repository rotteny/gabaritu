<script setup lang="ts">
import { RotateCcw } from '@lucide/vue';
import { niveisZoeira } from '@/data/landingDemo';
import type { Zoeira } from '@/data/landingDemo';

defineEmits<{ reiniciar: [] }>();

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
                aria-label="Recomeçar a questão"
                @click="$emit('reiniciar')"
            >
                <RotateCcw class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
