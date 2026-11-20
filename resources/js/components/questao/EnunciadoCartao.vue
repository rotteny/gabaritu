<script setup lang="ts">
import ZoomImagem from '@/components/questao/ZoomImagem.vue';
import type { ImagemQuestao } from '@/types/gabaritu';

defineProps<{
    contexto: string | null;
    enunciado: string;
    imagem?: ImagemQuestao | null;
    fonte?: string | null;
    compacto: boolean;
}>();
</script>

<template>
    <section
        class="q-cartao"
        :class="{
            'q-cartao--compacto': compacto,
            'q-cartao--imagem': imagem,
        }"
        aria-label="Pergunta"
    >
        <ZoomImagem
            v-if="imagem"
            class="q-figura"
            :imagem="imagem"
            :rotulo="`Ampliar imagem: ${imagem.alt}`"
        >
            <img
                class="q-figura-img"
                :src="imagem.url"
                :alt="imagem.alt"
                :width="imagem.largura"
                :height="imagem.altura"
            />
        </ZoomImagem>
        <div class="q-texto">
            <p
                v-if="contexto && !compacto"
                class="q-contexto"
                :tabindex="imagem ? 0 : undefined"
            >
                {{ contexto }}
            </p>
            <p class="q-enunciado">{{ enunciado }}</p>
            <p v-if="fonte" class="q-fonte">Fonte: {{ fonte }}</p>
        </div>
    </section>
</template>
