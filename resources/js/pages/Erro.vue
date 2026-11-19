<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import { useAluno } from '@/composables/useAluno';
import { rotas } from '@/lib/rotas';
import type { Fala } from '@/types/gabaritu';

const props = defineProps<{ status: number; fala: Fala }>();
const { aluno } = useAluno();

const MENSAGENS: Record<number, { titulo: string; texto: string }> = {
    404: {
        titulo: 'Página não encontrada',
        texto: 'O endereço não existe ou foi trocado.',
    },
    419: {
        titulo: 'Sua sessão expirou',
        texto: 'Recarregue a página e tente de novo.',
    },
    429: {
        titulo: 'Muitas tentativas',
        texto: 'Espere um minutinho e tente de novo.',
    },
    500: {
        titulo: 'Algo deu errado aqui',
        texto: 'O problema é nosso, não seu. Tente de novo daqui a pouco.',
    },
};
const mensagem = computed(
    () =>
        MENSAGENS[props.status] ?? {
            titulo: 'Algo deu errado',
            texto: 'Tente de novo daqui a pouco.',
        },
);
</script>

<template>
    <Head :title="mensagem.titulo" />
    <p class="gb-erro-codigo">{{ status }}</p>
    <h1 class="gb-titulo">{{ mensagem.titulo }}</h1>
    <p class="gb-texto">{{ mensagem.texto }}</p>
    <FalaPeixoto :fala="fala" grande />
    <div class="gb-acoes">
        <Link
            :href="aluno ? rotas.inicio : rotas.landing"
            class="l-btn l-btn--orange"
        >
            {{ aluno ? 'Voltar pro início' : 'Ir pra página inicial' }}
        </Link>
    </div>
</template>
