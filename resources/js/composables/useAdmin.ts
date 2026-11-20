import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AdminCompartilhado } from '@/types/admin';

/** Props compartilhadas do painel admin (URLs vêm do servidor, o prefixo é configurável). */
export function useAdmin() {
    const page = usePage();
    const admin = computed(
        () => (page.props.admin as AdminCompartilhado | null) ?? null,
    );

    return { admin };
}
