<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AssuntoCartao from '@/components/aluno/AssuntoCartao.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import { rotas } from '@/lib/rotas';
import type { AssuntoResumo, Fala } from '@/types/gabaritu';

defineProps<{
    materia: { slug: string; nome: string };
    assuntos: AssuntoResumo[];
    fala: Fala;
}>();
</script>

<template>
    <Head :title="materia.nome" />
    <Link :href="rotas.inicio" class="gb-voltar">Voltar pro início</Link>
    <h1 class="gb-titulo">{{ materia.nome }}</h1>
    <FalaPeixoto :fala="fala" />
    <p v-if="assuntos.length === 0" class="gb-aviso gb-aviso--info">
        Essa matéria ainda não tem assuntos disponíveis.
    </p>
    <div class="gb-lista">
        <AssuntoCartao v-for="a in assuntos" :key="a.slug" :assunto="a" />
    </div>
</template>
