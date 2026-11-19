<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

const form = useForm({ senha_atual: '', senha: '', senha_confirmation: '' });
const salvar = () =>
    form.put(rotas.conta + '/senha', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <form
        class="gb-cartao gb-form"
        novalidate
        aria-labelledby="senha-titulo"
        @submit.prevent="salvar"
    >
        <h2 id="senha-titulo" class="gb-subtitulo">Trocar a senha</h2>
        <Campo
            v-model="form.senha_atual"
            rotulo="Senha atual"
            tipo="password"
            autocomplete="current-password"
            :erro="form.errors.senha_atual"
        />
        <Campo
            v-model="form.senha"
            rotulo="Senha nova"
            tipo="password"
            autocomplete="new-password"
            ajuda="Pelo menos 8 caracteres."
            :erro="form.errors.senha"
        />
        <Campo
            v-model="form.senha_confirmation"
            rotulo="Repita a senha nova"
            tipo="password"
            autocomplete="new-password"
            :erro="form.errors.senha_confirmation"
        />
        <p
            v-if="form.recentlySuccessful"
            class="gb-aviso gb-aviso--ok"
            role="status"
        >
            Senha trocada.
        </p>
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange"
                :disabled="form.processing"
            >
                Trocar a senha
            </button>
        </div>
    </form>
</template>
