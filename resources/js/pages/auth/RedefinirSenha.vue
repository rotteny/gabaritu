<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

defineOptions({
    layout: {
        title: 'Criar senha nova',
        description: 'Escolha uma senha com pelo menos 8 caracteres.',
    },
});

const props = defineProps<{ token: string; email: string }>();

const form = useForm({
    token: props.token,
    email: props.email,
    senha: '',
    senha_confirmation: '',
});

const enviar = () =>
    form.post(rotas.redefinirSenha, {
        onFinish: () => form.reset('senha', 'senha_confirmation'),
    });
</script>

<template>
    <Head title="Criar senha nova" />
    <form class="gb-form" novalidate @submit.prevent="enviar">
        <Campo
            v-model="form.email"
            rotulo="E-mail"
            tipo="email"
            autocomplete="email"
            :erro="form.errors.email"
            obrigatorio
        />
        <Campo
            v-model="form.senha"
            rotulo="Senha nova"
            tipo="password"
            autocomplete="new-password"
            :erro="form.errors.senha"
            obrigatorio
        />
        <Campo
            v-model="form.senha_confirmation"
            rotulo="Repita a senha nova"
            tipo="password"
            autocomplete="new-password"
            :erro="form.errors.senha_confirmation"
            obrigatorio
        />
        <p v-if="form.errors.token" class="gb-erro" role="alert">
            {{ form.errors.token }}
        </p>
        <button
            type="submit"
            class="l-btn l-btn--orange"
            :disabled="form.processing"
        >
            Salvar a senha nova
        </button>
    </form>
</template>
