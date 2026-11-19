import { computed, ref } from 'vue';
import { alternativasDemo, questaoDemo } from '@/data/landingDemo';
import type { Zoeira } from '@/data/landingDemo';

export type HumorPeixoto = 'idle' | 'mad' | 'happy' | 'help';

const PUFF_POR_ERRO = 0.16;
const MAX_ERROS_NO_INCHACO = 3;

/** Estado da mini questão da landing: erro infla o Peixoto, acerto solta a festa. */
export function usePeixotoDemo() {
    const nivel = ref<Zoeira>('sarcastico');
    const erradas = ref<string[]>([]);
    const acertou = ref(false);
    const rodada = ref(0);

    const humor = computed<HumorPeixoto>(() => {
        if (acertou.value) {
            return 'happy';
        }

        return erradas.value.length > 0 ? 'mad' : 'idle';
    });

    const inchaco = computed(
        () =>
            1 +
            Math.min(erradas.value.length, MAX_ERROS_NO_INCHACO) *
                PUFF_POR_ERRO,
    );

    const fala = computed(() => {
        if (acertou.value) {
            return questaoDemo.falaAcerto;
        }

        const ultima = erradas.value.at(-1);
        const alternativa = alternativasDemo.find((a) => a.letra === ultima);

        return alternativa?.piadas?.[nivel.value] ?? questaoDemo.falaInicial;
    });

    function escolher(letra: string): 'acerto' | 'erro' | null {
        const alternativa = alternativasDemo.find((a) => a.letra === letra);

        if (!alternativa || acertou.value || erradas.value.includes(letra)) {
            return null;
        }

        rodada.value += 1;

        if (alternativa.correta) {
            acertou.value = true;

            return 'acerto';
        }

        erradas.value = [...erradas.value, letra];

        return 'erro';
    }

    function reiniciar() {
        erradas.value = [];
        acertou.value = false;
        rodada.value += 1;
    }

    return {
        nivel,
        erradas,
        acertou,
        humor,
        inchaco,
        fala,
        rodada,
        escolher,
        reiniciar,
    };
}
