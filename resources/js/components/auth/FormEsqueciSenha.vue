<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';

const props = defineProps<{ url: string }>();

const form = useForm({ email: '' });
const enviar = () => form.post(props.url);
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
        <button
            type="submit"
            class="l-btn l-btn--orange"
            :disabled="form.processing"
        >
            Enviar o link
        </button>
    </form>
</template>
