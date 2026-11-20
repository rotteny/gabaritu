<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import FormEntrar from '@/components/auth/FormEntrar.vue';
import { rotas } from '@/lib/rotas';

defineOptions({ layout: { title: 'Entrar' } });

defineProps<{ status?: string | null }>();
</script>

<template>
    <Head title="Entrar" />
    <Aviso v-if="status" tipo="ok">{{ status }}</Aviso>
    <FormEntrar :url="rotas.entrar" />
    <p class="gb-texto">
        <Link :href="rotas.esqueciSenha" class="gb-link"
            >Esqueci minha senha</Link
        >
    </p>
    <p class="gb-texto">
        Ainda não tem conta?
        <Link :href="rotas.cadastro" class="gb-link">Criar conta</Link>
    </p>
    <p class="gb-texto">
        É responsável?
        <Link :href="rotas.entrarResponsavel" class="gb-link"
            >Entre por aqui</Link
        >
    </p>
</template>
