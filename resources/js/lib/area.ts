import type { MateriaResumo } from '@/types/gabaritu';

export type CorArea = 'orange' | 'yellow' | 'slime' | 'cyan';

const CORES: Record<string, CorArea> = {
    Matemática: 'orange',
    Linguagens: 'yellow',
    Humanas: 'slime',
    Natureza: 'cyan',
};

export type GrupoArea = {
    area: string;
    cor: CorArea;
    materias: MateriaResumo[];
};

export function corDaArea(area: string): CorArea {
    const chave = Object.keys(CORES).find((k) => area.startsWith(k));

    return chave ? CORES[chave] : 'orange';
}

/** Agrupa por área do ENEM, na ordem em que as áreas aparecem. */
export function agruparPorArea(materias: MateriaResumo[]): GrupoArea[] {
    const grupos = new Map<string, GrupoArea>();

    for (const m of materias) {
        const g = grupos.get(m.area) ?? {
            area: m.area,
            cor: corDaArea(m.area),
            materias: [],
        };
        g.materias.push(m);
        grupos.set(m.area, g);
    }

    return [...grupos.values()];
}

export function formatarNota(nota: number | null): string {
    return nota === null ? '' : `~ ${nota}`;
}
