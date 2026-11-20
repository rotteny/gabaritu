<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import CartaoAluno from '@/components/responsavel/CartaoAluno.vue';
import type { AlunoAcompanhado, Fala } from '@/types/gabaritu';

defineProps<{ alunos: AlunoAcompanhado[]; fala?: Fala | null }>();
</script>

<template>
    <Head title="Meus alunos" />
    <h1 class="gb-titulo">Meus alunos</h1>
    <p class="gb-aviso gb-aviso--info">
        Aqui você acompanha o desempenho de quem está sob a sua
        responsabilidade, ajusta o teto de zoeira e gerencia a autorização. As
        notas são estimativas do Gabaritu, não a nota oficial do ENEM.
    </p>
    <p v-if="alunos.length === 0" class="gb-aviso">
        Você ainda não acompanha nenhum aluno. Quando um convite for aceito, o
        aluno aparece aqui.
    </p>
    <div class="gb-resp-grade">
        <CartaoAluno v-for="a in alunos" :key="a.id" :aluno="a" />
    </div>
    <FalaPeixoto v-if="fala" :fala="fala" />
</template>
