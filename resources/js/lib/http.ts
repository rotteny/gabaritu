export class ErroHttp extends Error {
    constructor(
        public status: number,
        message: string,
    ) {
        super(message);
    }
}

function tokenXsrf(): string {
    const achado = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='));

    return achado ? decodeURIComponent(achado.split('=')[1]) : '';
}

export type OpcoesPost = {
    /** Cancela a requisição depois desse tempo (erro com status 0). */
    timeoutMs?: number;
    /** Cancelamento externo (ex.: tela desmontada): rejeita com AbortError. */
    signal?: AbortSignal;
};

const MSG_PADRAO = 'Não deu pra enviar a resposta. Tenta de novo.';

function sinalDe({ timeoutMs, signal }: OpcoesPost): AbortSignal | undefined {
    const sinais = [
        signal,
        timeoutMs ? AbortSignal.timeout(timeoutMs) : undefined,
    ].filter((s): s is AbortSignal => s !== undefined);

    return sinais.length > 1 ? AbortSignal.any(sinais) : sinais[0];
}

/** Falha antes de haver resposta HTTP: timeout, sem rede ou cancelamento. */
function erroDeRede(e: unknown): unknown {
    if (e instanceof DOMException && e.name === 'TimeoutError') {
        return new ErroHttp(0, 'A resposta demorou demais. Tenta de novo.');
    }

    if (e instanceof TypeError) {
        return new ErroHttp(
            0,
            'Sem conexão. Confere a internet e tenta de novo.',
        );
    }

    return e;
}

/** POST JSON sem recarregar a página. Erros viram ErroHttp com mensagem legível. */
export async function postJson<T>(
    url: string,
    corpo: unknown,
    opcoes: OpcoesPost = {},
): Promise<T> {
    const resposta = await fetch(url, {
        signal: sinalDe(opcoes),
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': tokenXsrf(),
        },
        body: JSON.stringify(corpo),
    }).catch((e: unknown) => {
        throw erroDeRede(e);
    });

    if (!resposta.ok) {
        const dados = (await resposta.json().catch(() => ({}))) as {
            message?: string;
        };

        throw new ErroHttp(
            resposta.status,
            resposta.status < 500 ? (dados.message ?? MSG_PADRAO) : MSG_PADRAO,
        );
    }

    return (await resposta.json()) as T;
}
