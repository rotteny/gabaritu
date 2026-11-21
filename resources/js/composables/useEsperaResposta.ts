import { onBeforeUnmount, ref } from 'vue';

export const ATRASO_ESPERA_MS = 250;
export const MINIMO_ESPERA_MS = 400;

/**
 * Estado visual de "conferindo": só aparece se a resposta demorar mais que `atrasoMs`
 * (respostas rápidas não piscam) e, depois de aparecer, fica no mínimo `minimoMs`.
 */
export function useEsperaResposta(
    atrasoMs = ATRASO_ESPERA_MS,
    minimoMs = MINIMO_ESPERA_MS,
) {
    const visivel = ref(false);
    let timer = 0;
    let desde = 0;

    function iniciar() {
        cancelar();
        timer = window.setTimeout(() => {
            desde = Date.now();
            visivel.value = true;
        }, atrasoMs);
    }

    /** Resolve quando o estado de espera já pode sair de cena. */
    async function encerrar() {
        clearTimeout(timer);

        if (visivel.value) {
            const resta = minimoMs - (Date.now() - desde);

            if (resta > 0) {
                await new Promise((r) => setTimeout(r, resta));
            }
        }

        visivel.value = false;
    }

    function cancelar() {
        clearTimeout(timer);
        visivel.value = false;
    }

    onBeforeUnmount(cancelar);

    return { visivel, iniciar, encerrar, cancelar };
}
