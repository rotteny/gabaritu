import { postJson } from '@/lib/http';
import { rotas } from '@/lib/rotas';
import type { RespostaApi } from '@/types/gabaritu';

export type EnvioResposta = { questao_id: number; alternativa_id: number };

/** Prévia local (/_previa/*): a resposta vem de um mock em vez do endpoint. */
const naPrevia = () => window.location.pathname.startsWith('/_previa/');

export const TIMEOUT_RESPOSTA_MS = 15_000;

export async function enviarResposta(
    tentativaId: number,
    envio: EnvioResposta,
    signal?: AbortSignal,
): Promise<RespostaApi> {
    if (naPrevia()) {
        const { responderNaPrevia } =
            await import('@/fixtures/previaRespostas');

        return responderNaPrevia(envio);
    }

    return postJson<RespostaApi>(rotas.respostas(tentativaId), envio, {
        timeoutMs: TIMEOUT_RESPOSTA_MS,
        signal,
    });
}
