<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import EscolhaAvatar from '@/components/aluno/EscolhaAvatar.vue';
import { rotas } from '@/lib/rotas';
import type { AvatarAluno } from '@/types/gabaritu';

const props = defineProps<{ atual?: AvatarAluno | null }>();

const form = useForm({ avatar: props.atual ?? '' });
const salvar = () => form.patch(rotas.contaAvatar, { preserveScroll: true });
</script>

<template>
    <form
        class="gb-cartao gb-form"
        novalidate
        aria-labelledby="avatar-titulo"
        @submit.prevent="salvar"
    >
        <h2 id="avatar-titulo" class="gb-subtitulo">Seu bicho</h2>
        <EscolhaAvatar
            v-model="form.avatar"
            legenda="Escolha o bicho que representa você"
            :erro="form.errors.avatar"
        />
        <p
            v-if="form.recentlySuccessful"
            class="gb-aviso gb-aviso--ok"
            role="status"
        >
            Bicho trocado.
        </p>
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange"
                :disabled="form.processing || !form.avatar"
            >
                Salvar o bicho
            </button>
        </div>
    </form>
</template>
