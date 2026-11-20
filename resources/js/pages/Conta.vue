<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AvatarConta from '@/components/aluno/AvatarConta.vue';
import ApagarConta from '@/components/aluno/ApagarConta.vue';
import BotaoSair from '@/components/aluno/BotaoSair.vue';
import PerfilConta from '@/components/aluno/PerfilConta.vue';
import PreferenciasConta from '@/components/aluno/PreferenciasConta.vue';
import SenhaConta from '@/components/aluno/SenhaConta.vue';
import { useAluno } from '@/composables/useAluno';
import QuemAcompanha from '@/components/aluno/QuemAcompanha.vue';
import { rotas } from '@/lib/rotas';
import type { ConviteDoAluno, ResponsavelDoAlunoConta } from '@/types/gabaritu';

withDefaults(
    defineProps<{
        responsaveis?: ResponsavelDoAlunoConta[];
        convites_responsavel?: ConviteDoAluno[];
        pode_convidar?: boolean;
    }>(),
    {
        responsaveis: () => [],
        convites_responsavel: () => [],
        pode_convidar: true,
    },
);
const { aluno } = useAluno();
</script>

<template>
    <Head title="Conta" />
    <h1 class="gb-titulo">Sua conta</h1>
    <template v-if="aluno">
        <AvatarConta :atual="aluno.avatar" />
        <PreferenciasConta :preferencias="aluno.preferencias" />
        <PerfilConta :aluno="aluno" />
        <QuemAcompanha
            v-if="responsaveis.length > 0 || convites_responsavel.length > 0"
            :responsaveis="responsaveis"
            :convites="convites_responsavel"
            :pode-convidar="pode_convidar"
        />
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
