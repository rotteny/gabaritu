<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';
import Campo from '@/components/aluno/Campo.vue';
import { rotas } from '@/lib/rotas';

const props = defineProps<{ pedeParentesco?: boolean }>();

const aberto = ref(props.pedeParentesco === true);
const form = useForm({
    email_responsavel: '',
    parentesco_responsavel: 'mae',
    parentesco_outro: '',
});
const salvar = () =>
    form
        .transform((d) =>
            props.pedeParentesco
                ? d
                : { email_responsavel: d.email_responsavel },
        )
        .patch(rotas.emailResponsavel, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                aberto.value = false;
            },
        });
</script>

<template>
    <div class="gb-bloco">
        <h3>
            {{
                pedeParentesco
                    ? 'Quem é o seu responsável?'
                    : 'Digitou o e-mail errado?'
            }}
        </h3>
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
            <CampoParentesco
                v-if="pedeParentesco"
                v-model:parentesco="form.parentesco_responsavel"
                v-model:outro="form.parentesco_outro"
                rotulo="Parentesco dele com você"
                :erro-parentesco="form.errors.parentesco_responsavel"
                :erro-outro="form.errors.parentesco_outro"
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
