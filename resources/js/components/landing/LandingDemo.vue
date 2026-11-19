<script setup lang="ts">
import { useTemplateRef, watch } from 'vue';
import { usePodeFalar } from '@/composables/usePodeFalar';
import { useDemoSound } from '@/composables/useDemoSound';
import { useFalaDigitada } from '@/composables/useFalaDigitada';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { usePeixotoDemo } from '@/composables/usePeixotoDemo';
import { alternativasDemo, questaoDemo } from '@/data/landingDemo';
import DemoBurst from './DemoBurst.vue';
import DemoControls from './DemoControls.vue';
import DemoSlime from './DemoSlime.vue';
import LandingSection from './LandingSection.vue';
import PeixotoSvg from './PeixotoSvg.vue';

const {
    nivel,
    erradas,
    acertou,
    humor,
    inchaco,
    fala,
    rodada,
    escolher,
    reiniciar,
} = usePeixotoDemo();
const { tocar, bipe } = useDemoSound();
const reduzido = useMovimentoReduzido();
const palco = useTemplateRef<HTMLElement>('palco');
const podeFalar = usePodeFalar(() => palco.value);
const { digitado, falar } = useFalaDigitada(reduzido, bipe, podeFalar);
const falaHumor = {
    idle: 'neutro',
    mad: 'bravo',
    happy: 'feliz',
    help: 'ajuda',
} as const;

watch([fala, humor], ([texto, h]) => void falar(texto, falaHumor[h]), {
    immediate: true,
});

function responder(letra: string) {
    const resultado = escolher(letra);

    if (resultado) {
        tocar(resultado);
    }
}

function estadoDaAlternativa(
    letra: string,
    correta: boolean,
): 'certa' | 'errada' | 'livre' {
    if (acertou.value && correta) {
        return 'certa';
    }

    return erradas.value.includes(letra) ? 'errada' : 'livre';
}
</script>

<template>
    <LandingSection
        id="demo"
        titulo="Teste o Peixoto. Vai, erra de propósito."
        lead="Uma questão de verdade, em miniatura. Errar infla o baiacu, acertar solta a festa."
    >
        <div class="l-card l-demo">
            <DemoSlime :ativa="erradas.length > 0 && !acertou" />
            <DemoControls v-model:nivel="nivel" @reiniciar="reiniciar" />

            <div class="l-demo-grid">
                <div ref="palco" class="l-demo-fish">
                    <p class="l-speech" :data-humor="humor" aria-live="polite">
                        {{ digitado || fala }}
                    </p>
                    <div class="l-float">
                        <div class="l-puff" :style="{ '--puff': inchaco }">
                            <PeixotoSvg :humor="humor" />
                        </div>
                    </div>
                    <DemoBurst v-if="acertou" :key="rodada" />
                </div>

                <div>
                    <p class="l-label">{{ questaoDemo.materia }}</p>
                    <p class="l-question">{{ questaoDemo.enunciado }}</p>
                    <ul class="l-options">
                        <li v-for="a in alternativasDemo" :key="a.letra">
                            <button
                                type="button"
                                class="l-option"
                                :data-estado="
                                    estadoDaAlternativa(a.letra, a.correta)
                                "
                                :disabled="acertou || erradas.includes(a.letra)"
                                @click="responder(a.letra)"
                            >
                                <span class="l-option-letter">{{
                                    a.letra
                                }}</span>
                                {{ a.texto }}
                            </button>
                        </li>
                    </ul>
                    <p v-if="erradas.length > 0 || acertou" class="l-why">
                        <strong>Por quê:</strong> {{ questaoDemo.explicacao }}
                    </p>
                </div>
            </div>
        </div>
    </LandingSection>
</template>
