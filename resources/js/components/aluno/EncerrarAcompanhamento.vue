<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Aviso from '@/components/aluno/Aviso.vue';
import { rotas } from '@/lib/rotas';

const confirmando = ref(false);
const form = useForm({});
const erro = computed(
    () => (form.errors as Record<string, string>).acompanhamento,
);
const encerrar = () =>
    form.post(rotas.encerrarAcompanhamento, {
        preserveScroll: true,
        onSuccess: () => (confirmando.value = false),
    });
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="encerrar-titulo">
        <h2 id="encerrar-titulo" class="gb-subtitulo">
            Encerrar acompanhamento
        </h2>
        <p class="gb-texto">
            Você tem 18 anos ou mais. Ao encerrar, todos os responsáveis deixam
            de ver o seu desempenho e recebem um aviso por e-mail. Não dá para
            restaurar depois.
        </p>
        <Aviso v-if="erro" tipo="erro">
            {{ erro }}
        </Aviso>
        <div v-if="!confirmando" class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--white"
                @click="confirmando = true"
            >
                Encerrar acompanhamento
            </button>
        </div>
        <div v-else class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--perigo"
                :disabled="form.processing"
                @click="encerrar"
            >
                Sim, encerrar agora
            </button>
            <button
                type="button"
                class="l-btn l-btn--white"
                @click="confirmando = false"
            >
                Cancelar
            </button>
        </div>
    </section>
</template>
