<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LinkExterno from '@/components/LinkExterno.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import QuadroGrafia from '@/components/aluno/QuadroGrafia.vue';
import { useAluno } from '@/composables/useAluno';
import { AUTOR_URL, REPOSITORIO_PADRAO } from '@/lib/constantes';
import type { Fala } from '@/types/gabaritu';

const { gabaritu } = useAluno();

const fala: Fala = {
    texto: 'Pode ler: o nome é assim mesmo, com U.',
    humor: 'neutro',
    variacao_id: null,
};

const sentidos = [
    ['Gabarito', 'a lista de respostas certas da prova.'],
    ['Gabaritar', 'acertar todas as questões.'],
    ['"Gabaritou!"', 'o grito de quem gabaritou.'],
    [
        'Gabaritu',
        'esse grito escrito do jeito que se fala: no dia a dia, o "ou" vira "u". Virou nome próprio.',
    ],
];
</script>

<template>
    <Head title="Quem somos" />
    <h1 class="gb-titulo">Quem somos</h1>
    <FalaPeixoto :fala="fala" grande />

    <section class="gb-cartao gb-prosa" aria-labelledby="qs-que">
        <h2 id="qs-que" class="gb-subtitulo">O que é o Gabaritu</h2>
        <p class="gb-texto">
            Uma plataforma open source e gratuita, sem fins comerciais, pra
            estudar pro ENEM com o Peixoto, um baiacu que zoa o erro e nunca a
            pessoa. Toda zoeira vem com explicação.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="qs-nome">
        <h2 id="qs-nome" class="gb-subtitulo">Por que "Gabaritu", com U?</h2>
        <p class="gb-texto">A grafia é proposital, não é erro de ortografia.</p>
        <dl class="gb-definicoes">
            <template v-for="[termo, texto] in sentidos" :key="termo">
                <dt>{{ termo }}</dt>
                <dd>{{ texto }}</dd>
            </template>
        </dl>
        <QuadroGrafia />
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="qs-quem">
        <h2 id="qs-quem" class="gb-subtitulo">Quem faz</h2>
        <p class="gb-texto">
            Um projeto pessoal de código aberto. O código está
            <LinkExterno
                :href="gabaritu?.repositorio_url ?? REPOSITORIO_PADRAO"
                class="gb-link"
                >no repositório</LinkExterno
            >
            e o autor está
            <LinkExterno :href="AUTOR_URL" class="gb-link"
                >no GitHub</LinkExterno
            >.
        </p>
    </section>

    <section
        v-if="gabaritu"
        class="gb-cartao gb-prosa"
        aria-labelledby="qs-contato"
    >
        <h2 id="qs-contato" class="gb-subtitulo">Contato</h2>
        <p class="gb-texto">
            Escreva para
            <a :href="`mailto:${gabaritu.email_contato}`" class="gb-link">{{
                gabaritu.email_contato
            }}</a
            >.
        </p>
    </section>
</template>
