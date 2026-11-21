<script setup lang="ts">
import { computed } from 'vue';
import CabecalhoQuestao from '@/components/questao/CabecalhoQuestao.vue';
import EnunciadoCartao from '@/components/questao/EnunciadoCartao.vue';
import ExplicacaoCartao from '@/components/questao/ExplicacaoCartao.vue';
import ListaAlternativas from '@/components/questao/ListaAlternativas.vue';
import PalcoPeixoto from '@/components/questao/PalcoPeixoto.vue';
import { useQuestao } from '@/composables/useQuestao';
import type { Fala, QuestaoDados, Tentativa } from '@/types/gabaritu';

const props = defineProps<{
    tentativa: Tentativa;
    questao: QuestaoDados;
    fala: Fala;
}>();

const q = useQuestao({
    tentativa: computed(() => props.tentativa),
    questao: computed(() => props.questao),
    abertura: computed(() => props.fala),
});

const reagindo = computed(
    () => q.fase.value === 'reagindo' || q.fase.value === 'explicada',
);
const reacao = computed(() =>
    q.resposta.value ? (q.acertou.value ? 'acerto' : 'erro') : null,
);
const efeitos = computed(
    () => !q.reduzido.value && !q.efeitosPulados.value && !q.freio.value,
);
</script>

<template>
    <div class="q-tela" :class="{ 'q-tela--pulou': q.efeitosPulados.value }">
        <CabecalhoQuestao :tentativa="tentativa" />
        <PalcoPeixoto
            :humor="q.humor.value"
            :texto-completo="q.textoCompleto.value"
            :digitado="q.digitado.value"
            :reacao="efeitos ? reacao : null"
            :esperando="q.esperando.value"
            :pode-pular="q.fase.value === 'reagindo' && q.falando.value"
            @pular="q.pularAnimacao"
        />
        <main
            class="q-jogo"
            :data-fase="q.fase.value"
            :aria-busy="q.fase.value === 'enviando'"
        >
            <div class="q-conteudo">
                <p
                    v-if="q.erroEnvio.value"
                    class="gb-aviso gb-aviso--erro"
                    role="alert"
                >
                    {{ q.erroEnvio.value }}
                    <button
                        v-if="q.escolhida.value"
                        type="button"
                        class="q-tentar"
                        @click="q.tentarDeNovo"
                    >
                        Tentar de novo
                    </button>
                </p>
                <EnunciadoCartao
                    :contexto="questao.contexto"
                    :enunciado="questao.enunciado"
                    :imagem="questao.imagem"
                    :fonte="questao.fonte"
                    :compacto="reagindo"
                />
                <ListaAlternativas
                    :alternativas="questao.alternativas"
                    :fase="q.fase.value"
                    :escolhida="q.escolhida.value"
                    :resposta="q.resposta.value"
                    :efeitos="efeitos"
                    @escolher="q.responder"
                />
            </div>
            <ExplicacaoCartao
                v-if="q.fase.value === 'explicada' && q.resposta.value"
                :resposta="q.resposta.value"
                :ultima="q.ultima.value"
                @proxima="q.proxima"
            />
        </main>
    </div>
</template>
