<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useId } from 'vue';

const props = defineProps<{
    status: 'sessao_expirada' | null;
    acoes: { entrar: string };
}>();

const form = useForm({ email: '', senha: '' });
const idEmail = useId();
const idSenha = useId();

function enviar() {
    form.post(props.acoes.entrar, { onFinish: () => form.reset('senha') });
}
</script>

<template>
    <Head title="Entrar · Admin" />
    <form class="ad-cartao ad-form" @submit.prevent="enviar">
        <h1 class="ad-h2">Entrar no painel</h1>
        <p v-if="status === 'sessao_expirada'" class="ad-alerta" role="status">
            Sua sessão expirou por inatividade. Entre de novo.
        </p>
        <div class="ad-campo">
            <label :for="idEmail">E-mail</label>
            <input
                :id="idEmail"
                v-model="form.email"
                class="ad-input"
                type="email"
                autocomplete="username"
                required
                autofocus
            />
            <p v-if="form.errors.email" class="ad-erro" role="alert">
                {{ form.errors.email }}
            </p>
        </div>
        <div class="ad-campo">
            <label :for="idSenha">Senha</label>
            <input
                :id="idSenha"
                v-model="form.senha"
                class="ad-input"
                type="password"
                autocomplete="current-password"
                required
            />
        </div>
        <button type="submit" class="ad-btn" :disabled="form.processing">
            Entrar
        </button>
    </form>
</template>
