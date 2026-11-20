<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';

const props = defineProps<{ url: string }>();

const form = useForm({ email: '', senha: '', lembrar: false });
const enviar = () =>
    form.post(props.url, { onFinish: () => form.reset('senha') });
</script>

<template>
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
            rotulo="Senha"
            tipo="password"
            autocomplete="current-password"
            :erro="form.errors.senha"
            obrigatorio
        />
        <label class="gb-check">
            <input v-model="form.lembrar" type="checkbox" />
            <span>Manter conectado neste aparelho</span>
        </label>
        <button
            type="submit"
            class="l-btn l-btn--orange"
            :disabled="form.processing"
        >
            Entrar
        </button>
    </form>
</template>
