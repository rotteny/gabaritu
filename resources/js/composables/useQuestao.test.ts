import { ref } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Alternativa, Fala, RespostaApi } from '@/types/gabaritu';

const visit = vi.fn();
const enviar = vi.fn();
const falar = vi.fn(() => Promise.resolve(true));

vi.mock('vue', async (orig) => ({
    ...(await orig<typeof import('vue')>()),
    onBeforeUnmount: vi.fn(),
}));
vi.mock('@inertiajs/vue3', () => ({ router: { visit } }));
vi.mock('@/lib/respostas', () => ({ enviarResposta: enviar }));
vi.mock('@/composables/usePodeFalar', () => ({
    usePodeFalar: () => ref(true),
}));
vi.mock('@/composables/useMovimentoReduzido', () => ({
    useMovimentoReduzido: () => ref(false),
}));
vi.mock('@/composables/useMusica', () => ({
    useControleSom: () => ({ somLigado: ref(true) }),
}));
vi.mock('@/composables/useSomPeixoto', () => ({
    useSomPeixoto: () => ({
        destravar: vi.fn(),
        acerto: vi.fn(),
        erro: vi.fn(),
        bipe: vi.fn(),
    }),
}));
vi.mock('@/composables/useFalaDigitada', () => ({
    useFalaDigitada: () => ({
        digitado: ref(''),
        falando: ref(false),
        falar,
        pular: vi.fn(),
    }),
}));

const { useQuestao } = await import('@/composables/useQuestao');
const { ErroHttp } = await import('@/lib/http');

const alt = (id: number): Alternativa => ({ id }) as Alternativa;
const fala = (humor: Fala['humor']): Fala => ({
    texto: 'x',
    humor,
    variacao_id: null,
});
const api = (extra: Partial<RespostaApi> = {}): RespostaApi =>
    ({
        correta: true,
        alternativa_correta_id: 1,
        fala: fala('feliz'),
        freio_ativo: false,
        proxima: { url: '/p' },
        ...extra,
    }) as RespostaApi;

function montar() {
    return useQuestao({
        tentativa: ref({ id: 9, posicao: 1, total: 5 }) as never,
        questao: ref({ id: 3 }) as never,
        abertura: ref(fala('bravo')),
    });
}

describe('useQuestao', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.stubGlobal('window', globalThis);
        vi.stubGlobal('document', { querySelector: () => null });
    });
    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    it('clique duplo envia uma vez só', async () => {
        enviar.mockReturnValue(new Promise(() => {}));
        const q = montar();

        void q.responder(alt(1));
        void q.responder(alt(2));

        expect(enviar).toHaveBeenCalledTimes(1);
        expect(q.fase.value).toBe('enviando');
        expect(q.escolhida.value?.id).toBe(1);
    });

    it('resposta rápida não mostra a espera e a reação vem do JSON', async () => {
        enviar.mockResolvedValue(
            api({ correta: false, fala: fala('ajuda'), freio_ativo: true }),
        );
        const q = montar();

        await q.responder(alt(2));

        expect(q.esperando.value).toBe(false);
        expect(q.acertou.value).toBe(false);
        expect(q.freio.value).toBe(true);
        expect(q.humor.value).toBe('ajuda');
    });

    it('resposta lenta mostra a espera com humor neutro, sem reação prévia', async () => {
        let resolver!: (r: RespostaApi) => void;
        enviar.mockReturnValue(new Promise((r) => (resolver = r)));
        const q = montar();

        void q.responder(alt(1));
        await vi.advanceTimersByTimeAsync(300);

        expect(q.esperando.value).toBe(true);
        expect(q.humor.value).toBe('neutro');
        expect(q.resposta.value).toBeNull();

        resolver(api());
        await vi.advanceTimersByTimeAsync(500);

        expect(q.esperando.value).toBe(false);
        expect(q.acertou.value).toBe(true);
        expect(q.humor.value).toBe('feliz');
    });

    it('erro de rede destrava e mantém a escolha para tentar de novo', async () => {
        enviar.mockRejectedValueOnce(new ErroHttp(0, 'Sem conexão.'));
        const q = montar();

        await q.responder(alt(2));

        expect(q.fase.value).toBe('perguntando');
        expect(q.erroEnvio.value).toBe('Sem conexão.');
        expect(q.escolhida.value?.id).toBe(2);

        enviar.mockResolvedValueOnce(api());
        q.tentarDeNovo();
        await vi.advanceTimersByTimeAsync(0);

        expect(enviar).toHaveBeenCalledTimes(2);
        expect(enviar.mock.calls[1][1]).toMatchObject({ alternativa_id: 2 });
        expect(q.erroEnvio.value).toBeNull();
    });

    it('422 (já respondida) busca o estado real em vez de travar', async () => {
        enviar.mockRejectedValue(
            new ErroHttp(422, 'Essa questão já foi respondida.'),
        );
        const q = montar();

        await q.responder(alt(1));

        expect(visit).toHaveBeenCalledWith('/tentativas/9', { replace: true });
        expect(q.erroEnvio.value).toBeNull();
    });
});
