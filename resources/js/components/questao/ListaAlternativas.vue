<script setup lang="ts">
import AlternativaBotao from '@/components/questao/AlternativaBotao.vue';
import type { EstadoAlternativa } from '@/components/questao/AlternativaBotao.vue';
import type { FaseQuestao } from '@/composables/useQuestao';
import type { Alternativa, RespostaApi } from '@/types/gabaritu';

const props = defineProps<{
    alternativas: Alternativa[];
    fase: FaseQuestao;
    escolhida: Alternativa | null;
    resposta: RespostaApi | null;
    efeitos: boolean;
}>();
defineEmits<{ escolher: [alternativa: Alternativa] }>();

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
        :class="{ 'q-alts--travada': fase !== 'perguntando' }"
        aria-label="Alternativas"
    >
        <li v-for="a in alternativas" :key="a.id">
            <AlternativaBotao
                :alternativa="a"
                :estado="estadoDe(a)"
                :travada="fase !== 'perguntando'"
                :efeitos="efeitos"
                :escolhida="escolhida?.id === a.id"
                @escolher="fase === 'perguntando' && $emit('escolher', a)"
            />
        </li>
    </ul>
</template>
