import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PropsCompartilhadas } from '@/types/gabaritu';

/** Aluno logado e dados públicos do app, vindos das props compartilhadas. */
export function useAluno() {
    const page = usePage<PropsCompartilhadas>();

    const aluno = computed(() => page.props.auth?.user ?? null);
    const gabaritu = computed(() => page.props.gabaritu);
    const preferencias = computed(() => aluno.value?.preferencias ?? null);

    return { aluno, gabaritu, preferencias };
}
