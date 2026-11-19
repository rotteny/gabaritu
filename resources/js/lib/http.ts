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

/** POST JSON sem recarregar a página. Erros viram ErroHttp com mensagem legível. */
export async function postJson<T>(url: string, corpo: unknown): Promise<T> {
    const resposta = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': tokenXsrf(),
        },
        body: JSON.stringify(corpo),
    });

    if (!resposta.ok) {
        const dados = (await resposta.json().catch(() => ({}))) as {
            message?: string;
        };

        throw new ErroHttp(
            resposta.status,
            dados.message ?? 'Não deu pra enviar a resposta. Tenta de novo.',
        );
    }

    return (await resposta.json()) as T;
}
