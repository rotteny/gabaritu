import { afterEach, describe, expect, it, vi } from 'vitest';
import { ErroHttp, postJson } from '@/lib/http';

afterEach(() => vi.unstubAllGlobals());

function stubFetch(impl: () => Promise<unknown>) {
    vi.stubGlobal('document', { cookie: '' });
    vi.stubGlobal('fetch', vi.fn(impl));
}

describe('postJson', () => {
    it('falha de rede vira ErroHttp status 0', async () => {
        stubFetch(() => Promise.reject(new TypeError('Failed to fetch')));

        await expect(postJson('/x', {})).rejects.toMatchObject({ status: 0 });
    });

    it('timeout vira ErroHttp com mensagem de demora', async () => {
        stubFetch(() => Promise.reject(new DOMException('t', 'TimeoutError')));

        const e = await postJson('/x', {}).catch((x: unknown) => x);

        expect(e).toBeInstanceOf(ErroHttp);
        expect((e as ErroHttp).message).toMatch(/demorou/);
    });

    it('500 não vaza a mensagem crua do servidor', async () => {
        stubFetch(() =>
            Promise.resolve({
                ok: false,
                status: 500,
                json: () => Promise.resolve({ message: 'Server Error' }),
            }),
        );

        const e = (await postJson('/x', {}).catch(
            (x: unknown) => x,
        )) as ErroHttp;

        expect(e.status).toBe(500);
        expect(e.message).not.toBe('Server Error');
    });
});
