<script setup lang="ts">
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { humorPeixoto } from '@/lib/humor';
import type { Fala } from '@/types/gabaritu';

defineProps<{ fala: Fala; grande?: boolean }>();
</script>

<template>
    <div class="gb-fala" :class="{ 'gb-fala--grande': grande }">
        <div class="l-float" aria-hidden="true">
            <PeixotoSvg :humor="humorPeixoto(fala.humor)" />
        </div>
        <p
            class="l-speech"
            :data-humor="
                fala.humor === 'bravo'
                    ? 'mad'
                    : fala.humor === 'feliz'
                      ? 'happy'
                      : 'idle'
            "
        >
            <span class="gb-sr">O Peixoto diz:</span>
            {{ fala.texto }}
        </p>
    </div>
</template>
