<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatarNota } from '@/lib/area';
import { rotas } from '@/lib/rotas';
import type { AssuntoResumo } from '@/types/gabaritu';

defineProps<{ assunto: AssuntoResumo }>();

const iniciando = ref<number | null>(null);

function comecar(id: number) {
    iniciando.value = id;
    router.post(
        rotas.novaTentativa(id),
        {},
        {
            onFinish: () => (iniciando.value = null),
        },
    );
}
</script>

<template>
    <article class="gb-cartao gb-assunto">
        <header class="gb-assunto-topo">
            <h3 class="gb-subtitulo">{{ assunto.nome }}</h3>
            <p v-if="assunto.nota !== null" class="gb-nota">
                {{ formatarNota(assunto.nota) }}
                <span class="gb-miudo">nota estimada</span>
            </p>
            <p v-else class="gb-miudo">Sem nota ainda</p>
            <span
                v-if="assunto.amostra_baixa && assunto.nota !== null"
                class="gb-tag gb-tag--cinza"
            >
                pouca amostra
            </span>
        </header>
        <ul class="gb-questionarios">
            <li
                v-for="q in assunto.questionarios"
                :key="q.id"
                class="gb-questionario"
            >
                <div>
                    <p class="gb-questionario-titulo">{{ q.titulo }}</p>
                    <p v-if="q.feito" class="gb-miudo">
                        Feito<template v-if="q.melhor_acertos !== null"
                            >, melhor resultado:
                            {{ q.melhor_acertos }} acertos</template
                        >
                    </p>
                </div>
                <button
                    type="button"
                    class="l-btn"
                    :class="q.feito ? 'l-btn--white' : 'l-btn--orange'"
                    :disabled="iniciando !== null"
                    @click="comecar(q.id)"
                >
                    {{ q.feito ? 'Refazer' : 'Começar' }}
                    <span class="gb-sr">{{ q.titulo }}</span>
                </button>
            </li>
        </ul>
    </article>
</template>
