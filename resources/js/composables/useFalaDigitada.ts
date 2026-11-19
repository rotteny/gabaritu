import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import type { HumorFala } from '@/types/gabaritu';

const LETRA = /[A-Za-zÀ-ÿ0-9]/;
const PAUSA = /[.,!?]/;

const VELOCIDADE: Record<HumorFala, number> = {
    bravo: 17,
    feliz: 22,
    neutro: 26,
    ajuda: 30,
};

/**
 * Balão de fala que "digita" o texto, com um bipe por letra.
 * Só avança enquanto `pode` for true (Peixoto visível, aba ativa, áudio destravado); fora disso pausa e retoma.
 * `falar` resolve true quando termina (ou é pulada) e false se outra fala a interrompeu.
 */
export function useFalaDigitada(
    reduzido: Ref<boolean>,
    bipe: (humor: HumorFala) => void,
    pode: Ref<boolean> = ref(true),
) {
    const digitado = ref('');
    const falando = ref(false);
    let token = 0;
    let finalizar: (() => void) | null = null;
    let retomar: (() => void) | null = null;

    watch(pode, (v) => {
        if (v && retomar) {
            const r = retomar;
            retomar = null;
            r();
        }
    });

    function falar(texto: string, humor: HumorFala): Promise<boolean> {
        const meu = ++token;
        retomar = null;
        digitado.value = '';

        if (reduzido.value) {
            digitado.value = texto;
            falando.value = false;

            return Promise.resolve(true);
        }

        falando.value = true;

        return new Promise((resolve) => {
            let i = 0;
            let timer = 0;

            const concluir = () => {
                clearTimeout(timer);
                retomar = null;
                digitado.value = texto;
                falando.value = false;
                finalizar = null;
                resolve(true);
            };
            finalizar = concluir;

            const passo = () => {
                if (meu !== token) {
                    resolve(false);

                    return;
                }

                if (!pode.value) {
                    retomar = passo;

                    return;
                }

                i += 1;
                const letra = texto.charAt(i - 1);
                digitado.value = texto.slice(0, i);

                if (LETRA.test(letra)) {
                    bipe(humor);
                }

                if (i >= texto.length) {
                    concluir();

                    return;
                }

                timer = window.setTimeout(
                    passo,
                    PAUSA.test(letra)
                        ? VELOCIDADE[humor] * 4
                        : VELOCIDADE[humor],
                );
            };

            passo();
        });
    }

    function pular() {
        finalizar?.();
    }

    return { digitado, falando, falar, pular };
}
