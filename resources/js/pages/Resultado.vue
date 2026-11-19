<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import { rotas } from '@/lib/rotas';
import type { Fala } from '@/types/gabaritu';

const props = defineProps<{
    tentativa_id: number;
    acertos: number;
    total: number;
    erro_mais_comum: { etiqueta: string; nome: string; vezes: number } | null;
    nota_assunto: { antes: number | null; depois: number | null } | null;
    tem_erradas: boolean;
    proximo_questionario: { id: number; titulo: string } | null;
    fala: Fala;
}>();

const enviando = ref(false);

function criar(url: string) {
    enviando.value = true;
    router.post(url, {}, { onFinish: () => (enviando.value = false) });
}
</script>

<template>
    <Head title="Resultado" />
    <h1 class="gb-titulo">Seu resultado</h1>
    <section class="gb-cartao gb-placar" aria-label="Placar">
        <p class="gb-placar-num">
            <span class="gb-sr">Você acertou</span>
            {{ acertos }}<span class="gb-placar-de"> de {{ total }}</span>
        </p>
        <p class="gb-miudo">
            {{ acertos === 1 ? 'questão certa' : 'questões certas' }}
        </p>
    </section>
    <FalaPeixoto :fala="fala" grande />

    <section
        v-if="erro_mais_comum"
        class="gb-cartao"
        aria-labelledby="erro-comum"
    >
        <h2 id="erro-comum" class="gb-subtitulo">Seu erro mais comum</h2>
        <p class="gb-texto">
            <strong>{{ erro_mais_comum.nome }}</strong>
            ({{ erro_mais_comum.vezes }}
            {{ erro_mais_comum.vezes === 1 ? 'vez' : 'vezes' }}). Fica de olho
            nele na próxima.
        </p>
    </section>

    <section
        v-if="nota_assunto && nota_assunto.depois !== null"
        class="gb-cartao"
        aria-labelledby="nota-assunto"
    >
        <h2 id="nota-assunto" class="gb-subtitulo">Nota estimada do assunto</h2>
        <p class="gb-nota">
            <template v-if="nota_assunto.antes !== null"
                >~ {{ nota_assunto.antes }} para
            </template>
            ~ {{ nota_assunto.depois }}
        </p>
        <p class="gb-miudo">
            Estimativa do Gabaritu. Não é a nota oficial do ENEM.
        </p>
    </section>

    <div class="gb-acoes">
        <button
            v-if="tem_erradas"
            type="button"
            class="l-btn l-btn--yellow"
            :disabled="enviando"
            @click="criar(rotas.revisarErradas(tentativa_id))"
        >
            Revisar as erradas
        </button>
        <button
            v-if="proximo_questionario"
            type="button"
            class="l-btn l-btn--orange"
            :disabled="enviando"
            @click="criar(rotas.novaTentativa(proximo_questionario.id))"
        >
            Próximo questionário
        </button>
        <Link :href="rotas.inicio" class="l-btn l-btn--white"
            >Voltar pro início</Link
        >
    </div>
</template>
