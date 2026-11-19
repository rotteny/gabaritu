import { ref } from 'vue';
import { useSomPeixoto } from '@/composables/useSomPeixoto';

type Efeito = 'acerto' | 'erro';

/** Som da demo da landing: efeitos e voz, sobre o mesmo contexto de áudio do app. */
export function useDemoSound() {
    const ligado = ref(true);
    const som = useSomPeixoto(ligado);

    function tocar(efeito: Efeito) {
        som.destravar();

        if (efeito === 'acerto') {
            som.acerto();
        } else {
            som.erro();
        }
    }

    function alternar() {
        ligado.value = !ligado.value;

        if (ligado.value) {
            som.destravar();
        }
    }

    return { ligado, tocar, alternar, bipe: som.bipe };
}
