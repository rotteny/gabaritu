<script setup lang="ts">
import { Music, Slash } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ ligada: boolean; semSom?: boolean }>();
defineEmits<{ alternar: [] }>();

const rotulo = computed(() => {
    if (props.semSom) {
        return 'Música indisponível: o som está desligado';
    }

    return props.ligada
        ? 'Música ligada. Desligar a música'
        : 'Música desligada. Ligar a música';
});
</script>

<template>
    <button
        type="button"
        class="l-icon-btn"
        :aria-pressed="ligada && !semSom"
        :aria-label="rotulo"
        :disabled="semSom"
        @click="$emit('alternar')"
    >
        <Music class="col-start-1 row-start-1 size-5" aria-hidden="true" />
        <Slash
            v-if="!ligada || semSom"
            class="col-start-1 row-start-1 size-6"
            aria-hidden="true"
        />
    </button>
</template>
