<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginado } from '@/types/admin';

const props = defineProps<{
    paginado: Pick<
        Paginado<unknown>,
        'pagina' | 'ultima_pagina' | 'total' | 'por_pagina'
    >;
}>();
const page = usePage();

function hrefPara(n: number): string {
    const url = new URL(page.url, 'http://x');
    url.searchParams.set('page', String(n));

    return url.pathname + url.search;
}

const anterior = computed(() =>
    props.paginado.pagina > 1 ? hrefPara(props.paginado.pagina - 1) : null,
);
const proxima = computed(() =>
    props.paginado.pagina < props.paginado.ultima_pagina
        ? hrefPara(props.paginado.pagina + 1)
        : null,
);
</script>

<template>
    <nav v-if="paginado.total > 0" class="ad-pag" aria-label="Paginação">
        <p class="ad-mudo ad-num">
            {{ paginado.total.toLocaleString('pt-BR') }}
            {{ paginado.total === 1 ? 'resultado' : 'resultados' }} · página
            {{ paginado.pagina }} de {{ Math.max(paginado.ultima_pagina, 1) }}
        </p>
        <div v-if="paginado.ultima_pagina > 1" class="ad-pag-botoes">
            <Link
                v-if="anterior"
                :href="anterior"
                class="ad-btn ad-btn--claro ad-btn--mini"
                preserve-scroll
                >Anterior</Link
            >
            <Link
                v-if="proxima"
                :href="proxima"
                class="ad-btn ad-btn--claro ad-btn--mini"
                preserve-scroll
                >Próxima</Link
            >
        </div>
    </nav>
</template>
