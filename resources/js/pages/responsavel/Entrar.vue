<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import FormEntrar from '@/components/auth/FormEntrar.vue';
import { rotas } from '@/lib/rotas';

defineOptions({
    layout: {
        title: 'Entrar como responsável',
        description:
            'Para quem acompanha o estudo de um aluno. O convite chega por e-mail.',
    },
});

defineProps<{ status?: string | null }>();
</script>

<template>
    <Head title="Entrar como responsável" />
    <Aviso v-if="status" tipo="ok">{{ status }}</Aviso>
    <FormEntrar :url="rotas.entrarResponsavel" />
    <p class="gb-texto">
        <Link :href="rotas.esqueciSenhaResponsavel" class="gb-link"
            >Esqueci minha senha</Link
        >
    </p>
    <p class="gb-texto">
        Ainda não tem conta? Ela é criada quando você aceita o convite de um
        aluno, que chega por e-mail.
    </p>
    <p class="gb-texto">
        É aluno?
        <Link :href="rotas.entrar" class="gb-link">Entre por aqui</Link>
    </p>
</template>
