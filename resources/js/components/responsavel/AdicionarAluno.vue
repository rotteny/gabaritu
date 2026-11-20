<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';
import { rotas } from '@/lib/rotas';

defineProps<{ status?: string | null }>();

const aberto = ref(false);
const form = useForm({
    email: '',
    parentesco: 'mae',
    parentesco_outro: '',
    declara_responsavel: false,
});
const enviar = () =>
    form.post(rotas.pedidos, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            aberto.value = false;
        },
    });
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="add-aluno">
        <h2 id="add-aluno" class="gb-subtitulo">Adicionar aluno</h2>
        <p class="gb-texto">
            Quer acompanhar um aluno que já usa o Gabaritu? Informe o e-mail
            dele. Ele recebe o pedido e só vira vínculo depois que aceitar.
        </p>
        <Aviso v-if="status" tipo="ok">{{ status }}</Aviso>
        <div v-if="!aberto" class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--white"
                :aria-expanded="false"
                @click="aberto = true"
            >
                Adicionar aluno
            </button>
        </div>
        <form v-else class="gb-form" novalidate @submit.prevent="enviar">
            <Campo
                v-model="form.email"
                rotulo="E-mail do aluno"
                tipo="email"
                autocomplete="off"
                :erro="form.errors.email"
                obrigatorio
            />
            <CampoParentesco
                v-model:parentesco="form.parentesco"
                v-model:outro="form.parentesco_outro"
                rotulo="Seu parentesco com o aluno"
                :erro-parentesco="form.errors.parentesco"
                :erro-outro="form.errors.parentesco_outro"
            />
            <div>
                <label class="gb-check">
                    <input v-model="form.declara_responsavel" type="checkbox" />
                    <span>
                        Sou responsável legal por este aluno (pai, mãe, avó/avô,
                        tutor ou guardião com a guarda).
                    </span>
                </label>
                <p
                    v-if="form.errors.declara_responsavel"
                    class="gb-erro"
                    role="alert"
                >
                    {{ form.errors.declara_responsavel }}
                </p>
            </div>
            <div class="gb-acoes">
                <button
                    type="submit"
                    class="l-btn l-btn--orange"
                    :disabled="form.processing"
                >
                    Enviar pedido
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
    </section>
</template>
