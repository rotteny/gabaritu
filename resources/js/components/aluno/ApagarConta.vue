<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

const confirmando = ref(false);
const form = useForm({ senha: '' });
const apagar = () => form.delete(rotas.conta, { preserveScroll: true });
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="apagar-titulo">
        <h2 id="apagar-titulo" class="gb-subtitulo">Apagar minha conta</h2>
        <Aviso tipo="erro">
            Isso apaga a conta e tudo que você fez: respostas, notas e
            histórico. Não dá pra desfazer.
        </Aviso>
        <div v-if="!confirmando" class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--white"
                @click="confirmando = true"
            >
                Quero apagar minha conta
            </button>
        </div>
        <form v-else class="gb-form" novalidate @submit.prevent="apagar">
            <Campo
                v-model="form.senha"
                rotulo="Digite sua senha para confirmar"
                tipo="password"
                autocomplete="current-password"
                :erro="form.errors.senha"
            />
            <div class="gb-acoes">
                <button
                    type="submit"
                    class="l-btn l-btn--perigo"
                    :disabled="form.processing"
                >
                    Apagar tudo agora
                </button>
                <button
                    type="button"
                    class="l-btn l-btn--white"
                    @click="confirmando = false"
                >
                    Cancelar
                </button>
            </div>
        </form>
    </section>
</template>
