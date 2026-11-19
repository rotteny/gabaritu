<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';
import GrupoOpcoes from '@/components/aluno/GrupoOpcoes.vue';
import { rotas } from '@/lib/rotas';
import type { Aluno } from '@/types/gabaritu';

const props = defineProps<{ aluno: Aluno }>();

const SERIES = [
    { valor: '9ano', rotulo: '9º ano' },
    { valor: '1em', rotulo: '1º ano do médio' },
    { valor: '2em', rotulo: '2º ano do médio' },
    { valor: '3em', rotulo: '3º ano do médio' },
    { valor: 'cursinho', rotulo: 'Cursinho' },
];

const form = useForm({
    apelido: props.aluno.apelido,
    serie: props.aluno.serie as string,
});
const salvar = () =>
    form.patch(rotas.conta + '/perfil', { preserveScroll: true });
</script>

<template>
    <form
        class="gb-cartao gb-form"
        novalidate
        aria-labelledby="perfil-titulo"
        @submit.prevent="salvar"
    >
        <h2 id="perfil-titulo" class="gb-subtitulo">Perfil</h2>
        <Campo
            v-model="form.apelido"
            rotulo="Apelido"
            autocomplete="nickname"
            :erro="form.errors.apelido"
        />
        <GrupoOpcoes
            v-model="form.serie"
            legenda="Série"
            :opcoes="SERIES"
            :erro="form.errors.serie"
        />
        <p
            v-if="form.recentlySuccessful"
            class="gb-aviso gb-aviso--ok"
            role="status"
        >
            Perfil salvo.
        </p>
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange"
                :disabled="form.processing"
            >
                Salvar perfil
            </button>
        </div>
    </form>
</template>
