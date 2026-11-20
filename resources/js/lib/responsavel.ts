import type { Parentesco, Serie, TipoConvite } from '@/types/gabaritu';

export const PARENTESCOS: { valor: Parentesco; rotulo: string }[] = [
    { valor: 'pai', rotulo: 'Pai' },
    { valor: 'mae', rotulo: 'Mãe' },
    { valor: 'avo', rotulo: 'Avó ou avô' },
    { valor: 'tutor', rotulo: 'Tutor ou guardião legal' },
    { valor: 'outro', rotulo: 'Outro' },
];

export const SERIES_ROTULO: Record<Serie, string> = {
    '9ano': '9º ano',
    '1em': '1º ano do médio',
    '2em': '2º ano do médio',
    '3em': '3º ano do médio',
    cursinho: 'Cursinho',
};

export const TIPOS_CONVITE: Record<TipoConvite, string> = {
    autorizacao: 'autorização',
    coresponsavel: 'co-responsável',
    coresponsavel_aluno: 'co-responsável (pedido pelo aluno)',
    transferencia: 'transferência',
};

export const ehMenorDeIdade = (ano: string | number): boolean => {
    const n = Number(ano);
    if (!Number.isInteger(n) || n < 1900) return false;
    return new Date().getFullYear() - n - 1 < 18;
};

export const nomeDoVinculo = (r: {
    parentesco: Parentesco;
    parentesco_outro: string | null;
}): string => rotuloParentesco(r.parentesco, r.parentesco_outro);

export const rotuloParentesco = (
    valor: Parentesco,
    outro?: string | null,
): string => {
    if (valor === 'outro' && outro) return outro;
    return PARENTESCOS.find((p) => p.valor === valor)?.rotulo ?? valor;
};

export const formatarData = (iso: string): string =>
    new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        timeZone: 'America/Sao_Paulo',
    });

export const VERSAO_TEXTOS = '2026-11-22';
export const DATA_TEXTOS = '22 de novembro de 2026';
