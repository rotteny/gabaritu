<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ApagarConta from '@/components/aluno/ApagarConta.vue';
import BotaoSair from '@/components/aluno/BotaoSair.vue';
import PerfilConta from '@/components/aluno/PerfilConta.vue';
import PreferenciasConta from '@/components/aluno/PreferenciasConta.vue';
import SenhaConta from '@/components/aluno/SenhaConta.vue';
import { useAluno } from '@/composables/useAluno';
import { rotas } from '@/lib/rotas';

const { aluno } = useAluno();
</script>

<template>
    <Head title="Conta" />
    <h1 class="gb-titulo">Sua conta</h1>
    <template v-if="aluno">
        <PreferenciasConta :preferencias="aluno.preferencias" />
        <PerfilConta :aluno="aluno" />
        <SenhaConta />
        <ApagarConta />
        <div><BotaoSair /></div>
        <p class="gb-texto">
            Quer saber o que guardamos de você?
            <Link :href="rotas.privacidade" class="gb-link"
                >Ler a política de privacidade</Link
            >.
        </p>
    </template>
</template>
