import { router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

type Valor = string | null | undefined;

/** Filtros de listagem via GET (querystring). Vazio some da URL e a página volta para 1. */
export function useFiltros<T extends Record<string, Valor>>(inicial: T) {
    const page = usePage();
    const filtros = reactive({ ...inicial }) as T;

    function aplicar(extra: Record<string, Valor> = {}) {
        const dados: Record<string, string> = {};

        for (const [chave, valor] of Object.entries({ ...filtros, ...extra })) {
            if (valor !== null && valor !== undefined && valor !== '') {
                dados[chave] = String(valor);
            }
        }

        router.get(page.url.split('?')[0], dados, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function limpar() {
        for (const chave of Object.keys(filtros)) {
            (filtros as Record<string, Valor>)[chave] = null;
        }

        aplicar();
    }

    return { filtros, aplicar, limpar };
}
