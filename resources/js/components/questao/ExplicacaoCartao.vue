<script setup lang="ts">
import { useTemplateRef, onMounted } from 'vue';
import ReportarErro from '@/components/questao/ReportarErro.vue';
import type { RespostaApi } from '@/types/gabaritu';

defineProps<{ resposta: RespostaApi; ultima: boolean }>();
defineEmits<{ proxima: [] }>();

const botao = useTemplateRef<HTMLButtonElement>('botao');
onMounted(() => botao.value?.focus({ preventScroll: true }));
</script>

<template>
    <div class="q-depois">
        <section class="q-explicacao" aria-labelledby="q-porque">
            <h2 id="q-porque" class="q-explicacao-titulo">
                Por quê?
                <span v-if="resposta.etiqueta" class="gb-tag gb-tag--roxo">{{
                    resposta.etiqueta.nome
                }}</span>
            </h2>
            <p :class="{ 'q-explicacao-breve': !resposta.explicacao }">
                {{ resposta.explicacao ?? 'Explicação em breve' }}
            </p>
        </section>
        <div class="q-acoes">
            <ReportarErro v-if="resposta.report" :report="resposta.report" />
            <button
                ref="botao"
                type="button"
                class="l-btn l-btn--orange q-proxima"
                @click="$emit('proxima')"
            >
                {{ ultima ? 'Ver meu resultado' : 'Próxima questão' }}
            </button>
        </div>
    </div>
</template>
