<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

defineOptions({
    layout: {
        title: 'Esqueci minha senha',
        description:
            'Digite o e-mail da sua conta. Se ele existir, mandamos um link para criar uma senha nova.',
    },
});

defineProps<{ status?: string | null }>();

const form = useForm({ email: '' });
const enviar = () => form.post(rotas.esqueciSenha);
</script>

<template>
    <Head title="Esqueci minha senha" />
    <Aviso v-if="status" tipo="ok">{{ status }}</Aviso>
    <form class="gb-form" novalidate @submit.prevent="enviar">
        <Campo
            v-model="form.email"
            rotulo="E-mail"
            tipo="email"
            autocomplete="email"
            :erro="form.errors.email"
            obrigatorio
        />
        <button
            type="submit"
            class="l-btn l-btn--orange"
            :disabled="form.processing"
        >
            Enviar o link
        </button>
    </form>
    <p class="gb-texto">
        <Link :href="rotas.entrar" class="gb-link">Voltar para entrar</Link>
    </p>
</template>
