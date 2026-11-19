<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

defineOptions({ layout: { title: 'Entrar' } });

defineProps<{ status?: string | null }>();

const form = useForm({ email: '', senha: '', lembrar: false });

const enviar = () =>
    form.post(rotas.entrar, { onFinish: () => form.reset('senha') });
</script>

<template>
    <Head title="Entrar" />
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
    <p class="gb-texto">
        <Link :href="rotas.esqueciSenha" class="gb-link"
            >Esqueci minha senha</Link
        >
    </p>
    <p class="gb-texto">
        Ainda não tem conta?
        <Link :href="rotas.cadastro" class="gb-link">Criar conta</Link>
    </p>
</template>
