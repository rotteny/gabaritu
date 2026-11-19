<script setup lang="ts">
import { computed } from 'vue';
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { ehGrito, humorDaFala } from '@/lib/grito';
import { humorPeixoto } from '@/lib/humor';
import type { Fala } from '@/types/gabaritu';

const props = defineProps<{ fala: Fala; grande?: boolean }>();
const grito = computed(() => ehGrito(props.fala.texto));
const humor = computed(() => humorDaFala(props.fala.texto, props.fala.humor));
</script>

<template>
    <div class="gb-fala" :class="{ 'gb-fala--grande': grande }">
        <div class="l-float" aria-hidden="true">
            <PeixotoSvg :humor="humorPeixoto(humor)" />
        </div>
        <p
            class="l-speech gb-grito-alvo"
            :data-grito="grito"
            :data-humor="
                humor === 'bravo' ? 'mad' : humor === 'feliz' ? 'happy' : 'idle'
            "
        >
            <span class="gb-sr">O Peixoto diz:</span>
            {{ fala.texto }}
        </p>
    </div>
</template>
