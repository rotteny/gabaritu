<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import AdicionarAluno from '@/components/responsavel/AdicionarAluno.vue';
import PedidosEnviados from '@/components/responsavel/PedidosEnviados.vue';
import CartaoAluno from '@/components/responsavel/CartaoAluno.vue';
import type { AlunoAcompanhado, Fala, PedidoEnviado } from '@/types/gabaritu';

withDefaults(
    defineProps<{
        alunos: AlunoAcompanhado[];
        pedidos_enviados?: PedidoEnviado[];
        status?: string | null;
        fala?: Fala | null;
    }>(),
    { pedidos_enviados: () => [], status: null, fala: null },
);
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
    <div class="gb-resp-grade">
        <AdicionarAluno :status="status" />
        <PedidosEnviados :pedidos="pedidos_enviados" />
    </div>
    <FalaPeixoto v-if="fala" :fala="fala" />
</template>
