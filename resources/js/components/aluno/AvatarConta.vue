<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import EscolhaAvatar from '@/components/aluno/EscolhaAvatar.vue';
import { rotas } from '@/lib/rotas';
import type { AvatarAluno } from '@/types/gabaritu';

const props = defineProps<{
    atual?: AvatarAluno | null;
    avatares?: string[];
}>();

const form = useForm({ avatar: props.atual ?? '' });
const salvar = () => form.patch(rotas.contaPerfil, { preserveScroll: true });
</script>

<template>
    <form
        class="gb-cartao gb-form"
        novalidate
        aria-labelledby="avatar-titulo"
        @submit.prevent="salvar"
    >
        <h2 id="avatar-titulo" class="gb-subtitulo">Seu avatar</h2>
        <EscolhaAvatar
            v-model="form.avatar"
            :avatares="avatares"
            legenda="Escolha o avatar que representa você"
            :erro="form.errors.avatar"
        />
        <p
            v-if="form.hasErrors && !form.errors.avatar"
            class="gb-aviso gb-aviso--erro"
            role="alert"
        >
            Não deu para trocar o avatar. Tente de novo.
        </p>
        <p
            v-if="form.recentlySuccessful"
            class="gb-aviso gb-aviso--ok"
            role="status"
        >
            Avatar trocado.
        </p>
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange"
                :disabled="form.processing || !form.avatar"
            >
                Salvar o avatar
            </button>
        </div>
    </form>
</template>
