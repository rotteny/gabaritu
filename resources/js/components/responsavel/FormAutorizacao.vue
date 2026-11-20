<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Campo from '@/components/aluno/Campo.vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';
import DeclaracoesAceite from '@/components/responsavel/DeclaracoesAceite.vue';
import { rotas } from '@/lib/rotas';
import type { ConviteDados } from '@/types/gabaritu';

const props = defineProps<{
    token: string;
    convite: ConviteDados;
    semConta: boolean;
}>();

const form = useForm({
    nome: '',
    senha: '',
    senha_confirmation: '',
    ano_nascimento: '' as string | number,
    parentesco: props.convite.parentesco as string,
    parentesco_outro: props.convite.parentesco_outro ?? '',
    declara_responsavel: false,
    aceite_termos: false,
});

const enviar = () =>
    form
        .transform((d) =>
            props.semConta
                ? d
                : {
                      parentesco: d.parentesco,
                      parentesco_outro: d.parentesco_outro,
                      declara_responsavel: d.declara_responsavel,
                      aceite_termos: d.aceite_termos,
                  },
        )
        .post(rotas.conviteResponsavel(props.token), {
            onFinish: () => form.reset('senha', 'senha_confirmation'),
        });
const anoMax = computed(() => new Date().getFullYear() - 18);
</script>

<template>
    <form
        class="gb-cartao gb-form"
        novalidate
        aria-labelledby="convite-form"
        @submit.prevent="enviar"
    >
        <h2 id="convite-form" class="gb-subtitulo">
            {{
                semConta ? 'Crie a sua conta e autorize' : 'Confirme e autorize'
            }}
        </h2>
        <template v-if="semConta">
            <Campo
                v-model="form.nome"
                rotulo="Seu nome"
                autocomplete="name"
                :erro="form.errors.nome"
                obrigatorio
            />
            <p class="gb-campo">
                <span class="gb-legenda">E-mail (do convite)</span>
                <span class="gb-aviso">{{ convite.email }}</span>
            </p>
            <Campo
                v-model="form.senha"
                rotulo="Senha"
                tipo="password"
                autocomplete="new-password"
                ajuda="Pelo menos 8 caracteres."
                :erro="form.errors.senha"
                obrigatorio
            />
            <Campo
                v-model="form.senha_confirmation"
                rotulo="Repita a senha"
                tipo="password"
                autocomplete="new-password"
                :erro="form.errors.senha_confirmation"
                obrigatorio
            />
            <Campo
                v-model="form.ano_nascimento"
                rotulo="Seu ano de nascimento"
                tipo="number"
                :min="1900"
                :max="anoMax"
                autocomplete="bday-year"
                ajuda="Só para confirmar que você tem 18 anos ou mais."
                :erro="form.errors.ano_nascimento"
                obrigatorio
            />
        </template>
        <CampoParentesco
            v-model:parentesco="form.parentesco"
            v-model:outro="form.parentesco_outro"
            rotulo="Seu parentesco com o aluno"
            :erro-parentesco="form.errors.parentesco"
            :erro-outro="form.errors.parentesco_outro"
        />
        <DeclaracoesAceite
            v-model:declara="form.declara_responsavel"
            v-model:aceite="form.aceite_termos"
            :erro-declara="form.errors.declara_responsavel"
            :erro-aceite="form.errors.aceite_termos"
        />
        <p class="gb-miudo">
            Guardamos data e hora, IP, versão dos termos e estas declarações,
            como prova da autorização.
        </p>
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange"
                :disabled="form.processing"
            >
                Autorizar o acesso
            </button>
        </div>
    </form>
</template>
