import type { HumorFala } from '@/types/gabaritu';

const LETRAS_MAIUSCULAS = /\p{Lu}/gu;
const LETRAS = /\p{L}/gu;
const LIMITE_GRITO = 0.6;

/** A fala é um grito quando mais de 60% das letras estão em maiúscula. */
export function ehGrito(texto: string): boolean {
    const total = texto.match(LETRAS)?.length ?? 0;

    if (total === 0) {
        return false;
    }

    const maiusculas = texto.match(LETRAS_MAIUSCULAS)?.length ?? 0;

    return maiusculas / total > LIMITE_GRITO;
}

/** Grito sempre deixa o Peixoto bravo e inchado, seja qual for o humor do contrato. */
export function humorDaFala(texto: string, humor: HumorFala): HumorFala {
    return ehGrito(texto) ? 'bravo' : humor;
}
