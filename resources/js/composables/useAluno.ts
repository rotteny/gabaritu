import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type {
    Aluno,
    PropsCompartilhadas,
    UsuarioResponsavel,
} from '@/types/gabaritu';

/** Aluno logado e dados públicos do app, vindos das props compartilhadas. */
export function useAluno() {
    const page = usePage<PropsCompartilhadas>();

    const usuario = computed(() => page.props.auth?.user ?? null);
    const aluno = computed<Aluno | null>(() =>
        usuario.value && usuario.value.tipo !== 'responsavel'
            ? usuario.value
            : null,
    );
    const responsavel = computed<UsuarioResponsavel | null>(() =>
        usuario.value?.tipo === 'responsavel' ? usuario.value : null,
    );
    const gabaritu = computed(() => page.props.gabaritu);
    const preferencias = computed(() => aluno.value?.preferencias ?? null);

    return { aluno, responsavel, gabaritu, preferencias };
}
