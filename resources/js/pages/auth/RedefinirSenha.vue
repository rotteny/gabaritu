<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FormRedefinirSenha from '@/components/auth/FormRedefinirSenha.vue';
import { rotas } from '@/lib/rotas';

defineOptions({
    layout: {
        title: 'Criar senha nova',
        description: 'Escolha uma senha com pelo menos 8 caracteres.',
    },
});

defineProps<{ token: string; email: string }>();
</script>

<template>
    <Head title="Criar senha nova" />
    <FormRedefinirSenha
        :url="rotas.redefinirSenha"
        :token="token"
        :email="email"
    />
</template>
