<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

const aberto = ref(false);
const form = useForm({ email_responsavel: '' });
const salvar = () =>
    form.patch(rotas.emailResponsavel, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            aberto.value = false;
        },
    });
</script>

<template>
    <div class="gb-bloco">
        <h3>Digitou o e-mail errado?</h3>
        <div v-if="!aberto" class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--white"
                @click="aberto = true"
            >
                Corrigir o e-mail
            </button>
        </div>
        <form v-else class="gb-form" novalidate @submit.prevent="salvar">
            <Campo
                v-model="form.email_responsavel"
                rotulo="E-mail do seu responsável"
                tipo="email"
                autocomplete="off"
                ajuda="O convite anterior deixa de valer e um novo é enviado."
                :erro="form.errors.email_responsavel"
                obrigatorio
            />
            <div class="gb-acoes">
                <button
                    type="submit"
                    class="l-btn l-btn--orange"
                    :disabled="form.processing"
                >
                    Salvar e reenviar
                </button>
                <button
                    type="button"
                    class="l-btn l-btn--white"
                    @click="aberto = false"
                >
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</template>
