import dados from '@/fixtures/previaResposta.json';
import type { EnvioResposta } from '@/lib/respostas';
import type { Fala, RespostaApi } from '@/types/gabaritu';

const espera = (ms: number) => new Promise((r) => setTimeout(r, ms));

/** Simula o endpoint de respostas na prévia local. `?freio=1` liga o modo ajuda. */
export async function responderNaPrevia(
    envio: EnvioResposta,
): Promise<RespostaApi> {
    await espera(250);

    const correta = envio.alternativa_id === dados.alternativa_correta_id;
    const freio = new URLSearchParams(window.location.search).has('freio');
    const fala = (
        correta ? dados.certa : freio ? dados.freio : dados.errada
    ) as Omit<Fala, 'variacao_id'>;
    const ultima = window.location.pathname.endsWith('-ultima');

    return {
        correta,
        alternativa_correta_id: dados.alternativa_correta_id,
        etiqueta: correta ? null : { id: 1, nome: 'Confundiu as organelas' },
        explicacao: dados.explicacao,
        fala: { ...fala, variacao_id: null },
        freio_ativo: !correta && freio,
        chute: false,
        proxima: ultima
            ? { resultado_url: '/_previa/resultado' }
            : { url: '/_previa/questao-ultima' },
    };
}
