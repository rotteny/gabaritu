import { toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { bipePeixoto, destravarSom, somAcerto, somErro } from '@/lib/motorSom';
import type { HumorFala } from '@/types/gabaritu';

/** Voz em "peixês" e efeitos de acerto/erro sobre o contexto de áudio único. Respeita a preferência de som. */
export function useSomPeixoto(ligado: MaybeRefOrGetter<boolean>) {
    const se =
        <A extends unknown[]>(fn: (...a: A) => void) =>
        (...a: A) => {
            if (toValue(ligado)) {
                fn(...a);
            }
        };

    return {
        destravar: destravarSom,
        bipe: se((humor: HumorFala, grito?: boolean) =>
            bipePeixoto(humor, grito ?? false),
        ),
        acerto: se(somAcerto),
        erro: se(somErro),
    };
}
