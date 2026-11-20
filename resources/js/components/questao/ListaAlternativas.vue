<script setup lang="ts">
import { Maximize2 } from '@lucide/vue';
import { computed } from 'vue';
import AlternativaBotao from '@/components/questao/AlternativaBotao.vue';
import type { EstadoAlternativa } from '@/components/questao/AlternativaBotao.vue';
import type { FaseQuestao } from '@/composables/useQuestao';
import ZoomImagem from '@/components/questao/ZoomImagem.vue';
import type { Alternativa, RespostaApi } from '@/types/gabaritu';

const props = defineProps<{
    alternativas: Alternativa[];
    fase: FaseQuestao;
    escolhida: Alternativa | null;
    resposta: RespostaApi | null;
    efeitos: boolean;
}>();
defineEmits<{ escolher: [alternativa: Alternativa] }>();

const emGrade = computed(() => props.alternativas.some((a) => a.imagem));

function estadoDe(a: Alternativa): EstadoAlternativa {
    const r = props.resposta;

    if (!r) {
        return props.escolhida?.id === a.id ? 'pendente' : 'livre';
    }

    if (a.id === r.alternativa_correta_id) {
        return 'certa';
    }

    return props.escolhida?.id === a.id ? 'errada' : 'recolhida';
}
</script>

<template>
    <ul
        class="q-alts"
        :class="{
            'q-alts--travada': fase !== 'perguntando',
            'q-alts--grade': emGrade,
        }"
        aria-label="Alternativas"
    >
        <li v-for="a in alternativas" :key="a.id" class="q-alts-item">
            <AlternativaBotao
                :alternativa="a"
                :estado="estadoDe(a)"
                :travada="fase !== 'perguntando'"
                :efeitos="efeitos"
                :escolhida="escolhida?.id === a.id"
                @escolher="fase === 'perguntando' && $emit('escolher', a)"
            />
            <ZoomImagem
                v-if="a.imagem && estadoDe(a) !== 'recolhida'"
                class="q-alt-zoom"
                :imagem="a.imagem"
                :rotulo="`Ampliar alternativa ${a.letra}`"
            >
                <Maximize2 :size="16" aria-hidden="true" />
            </ZoomImagem>
        </li>
    </ul>
</template>
