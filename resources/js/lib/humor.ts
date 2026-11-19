import type { HumorPeixoto } from '@/composables/usePeixotoDemo';
import type { HumorFala } from '@/types/gabaritu';

const MAPA: Record<HumorFala, HumorPeixoto> = {
    feliz: 'happy',
    bravo: 'mad',
    neutro: 'idle',
    ajuda: 'help',
};

/** Humor da fala (contrato) para o estado visual do Peixoto. */
export function humorPeixoto(humor: HumorFala): HumorPeixoto {
    return MAPA[humor];
}

export const INCHACO: Record<HumorFala, number> = {
    bravo: 1.28,
    feliz: 1,
    neutro: 1,
    ajuda: 1,
};
