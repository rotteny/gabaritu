<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import FormEsqueciSenha from '@/components/auth/FormEsqueciSenha.vue';
import { rotas } from '@/lib/rotas';

defineOptions({
    layout: {
        title: 'Esqueci minha senha',
        description:
            'Digite o e-mail da sua conta de responsável. Se ele existir, mandamos um link para criar uma senha nova.',
    },
});

defineProps<{ status?: string | null }>();
</script>

<template>
    <Head title="Esqueci minha senha" />
    <Aviso v-if="status" tipo="ok">{{ status }}</Aviso>
    <FormEsqueciSenha :url="rotas.esqueciSenhaResponsavel" />
    <p class="gb-texto">
        <Link :href="rotas.entrarResponsavel" class="gb-link"
            >Voltar para entrar</Link
        >
    </p>
</template>
