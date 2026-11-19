import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import { useAluno } from '@/composables/useAluno';

/** Movimento reduzido: preferência do sistema OU preferência salva na conta. */
export function useMovimentoReduzido() {
    const { preferencias } = useAluno();
    const doSistema = useMediaQuery('(prefers-reduced-motion: reduce)');

    return computed(
        () =>
            doSistema.value || (preferencias.value?.reduzir_movimento ?? false),
    );
}
