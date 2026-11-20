<script setup lang="ts">
import DemoSlime from '@/components/landing/DemoSlime.vue';
import ExplosaoEstrela from '@/components/questao/ExplosaoEstrela.vue';
import type { Alternativa } from '@/types/gabaritu';

export type EstadoAlternativa =
    | 'livre'
    | 'pendente'
    | 'certa'
    | 'errada'
    | 'recolhida';

defineProps<{
    alternativa: Alternativa;
    estado: EstadoAlternativa;
    travada: boolean;
    efeitos: boolean;
    escolhida: boolean;
}>();
defineEmits<{ escolher: [] }>();
</script>

<template>
    <button
        type="button"
        class="q-alt"
        :class="[
            `q-alt--${estado}`,
            { 'q-alt--miniatura': alternativa.imagem },
        ]"
        :aria-disabled="travada"
        :aria-hidden="estado === 'recolhida' ? 'true' : undefined"
        :tabindex="estado === 'recolhida' ? -1 : undefined"
        @click="$emit('escolher')"
    >
        <span class="q-alt-letra" aria-hidden="true">{{
            alternativa.letra
        }}</span>
        <img
            v-if="alternativa.imagem"
            class="q-alt-img"
            :src="alternativa.imagem.url"
            :alt="alternativa.imagem.alt"
            :width="alternativa.imagem.largura"
            :height="alternativa.imagem.altura"
        />
        <span class="q-alt-texto">
            <span class="gb-sr">Alternativa {{ alternativa.letra }}.</span>
            {{ alternativa.texto }}
            <span v-if="estado === 'certa'" class="gb-sr">
                Resposta certa.</span
            >
            <span v-if="estado === 'errada'" class="gb-sr">
                Resposta errada.</span
            >
        </span>
        <template v-if="efeitos && escolhida">
            <ExplosaoEstrela :tipo="estado === 'certa' ? 'acerto' : 'erro'" />
            <DemoSlime v-if="estado === 'errada'" :ativa="true" />
        </template>
    </button>
</template>
