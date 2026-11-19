<script setup lang="ts">
import { Volume2, VolumeX } from '@lucide/vue';
import { useAlternarSom } from '@/composables/useMusica';

const { ligado, alternar } = useAlternarSom();
</script>

<template>
    <button
        type="button"
        class="l-icon-btn"
        :aria-pressed="ligado"
        :aria-label="
            ligado
                ? 'Som e música ligados. Desligar'
                : 'Som e música desligados. Ligar'
        "
        @click="alternar"
    >
        <Volume2 v-if="ligado" :size="22" aria-hidden="true" />
        <VolumeX v-else :size="22" aria-hidden="true" />
    </button>
</template>
