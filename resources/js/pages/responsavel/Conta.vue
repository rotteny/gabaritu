<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BotaoSair from '@/components/aluno/BotaoSair.vue';
import SenhaConta from '@/components/aluno/SenhaConta.vue';
import ApagarContaResponsavel from '@/components/responsavel/ApagarContaResponsavel.vue';
import DadosResponsavel from '@/components/responsavel/DadosResponsavel.vue';
import { rotas } from '@/lib/rotas';

withDefaults(
    defineProps<{
        responsavel: {
            nome: string;
            email: string;
            ano_nascimento?: number | null;
        };
        /** Apelidos dos alunos que só têm este responsável. */
        alunos_unicos?: string[];
    }>(),
    { alunos_unicos: () => [] },
);
</script>

<template>
    <Head title="Conta" />
    <h1 class="gb-titulo">Sua conta</h1>
    <DadosResponsavel
        :nome="responsavel.nome"
        :email="responsavel.email"
        :ano-nascimento="responsavel.ano_nascimento"
    />
    <SenhaConta :url="`${rotas.contaResponsavel}/senha`" />
    <ApagarContaResponsavel :bloqueado-por="alunos_unicos" />
    <div><BotaoSair /></div>
    <p class="gb-texto">
        Quer saber o que guardamos?
        <Link :href="rotas.privacidade" class="gb-link"
            >Política de privacidade</Link
        >
        e <Link :href="rotas.termos" class="gb-link">Termos de uso</Link>.
    </p>
</template>
