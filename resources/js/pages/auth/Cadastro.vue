<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Aviso from '@/components/aluno/Aviso.vue';
import EscolhaAvatar from '@/components/aluno/EscolhaAvatar.vue';
import Campo from '@/components/aluno/Campo.vue';
import ConviteNoCadastro from '@/components/responsavel/ConviteNoCadastro.vue';
import ResponsavelNoCadastro from '@/components/responsavel/ResponsavelNoCadastro.vue';
import GrupoOpcoes from '@/components/aluno/GrupoOpcoes.vue';
import { ehMenorDeIdade } from '@/lib/responsavel';
import { rotas } from '@/lib/rotas';
import type { ConviteDoCadastro } from '@/types/gabaritu';

defineOptions({
    layout: {
        title: 'Criar conta',
        description: 'Leva um minutinho. Você só precisa ter 13 anos ou mais.',
    },
});

const SERIES = [
    { valor: '9ano', rotulo: '9º ano' },
    { valor: '1em', rotulo: '1º ano do médio' },
    { valor: '2em', rotulo: '2º ano do médio' },
    { valor: '3em', rotulo: '3º ano do médio' },
    { valor: 'cursinho', rotulo: 'Cursinho' },
];

const props = defineProps<{
    convite_responsavel?: ConviteDoCadastro | null;
}>();

const form = useForm({
    apelido: '',
    email: props.convite_responsavel?.email ?? '',
    senha: '',
    senha_confirmation: '',
    ano_nascimento: '' as string | number,
    serie: '2em',
    avatar: '',
    email_responsavel: '',
    parentesco_responsavel: 'mae',
    parentesco_outro: '',
    confirma_responsavel: false,
    aceite_privacidade: false,
});

const menor = computed(() => ehMenorDeIdade(form.ano_nascimento));

// Com o convite confirmado, o aluno menor nasce vinculado e não precisa indicar outro e-mail.
const pedeResponsavel = computed(
    () =>
        menor.value &&
        !(props.convite_responsavel && form.confirma_responsavel),
);

const enviar = () =>
    form
        .transform((d) => {
            const { confirma_responsavel, ...resto } = d;
            const base: Record<string, unknown> = {
                ...resto,
                avatar: d.avatar || undefined,
            };
            if (props.convite_responsavel) {
                base.convite = props.convite_responsavel.token;
                base.confirma_responsavel = confirma_responsavel;
            }
            if (!pedeResponsavel.value) {
                base.email_responsavel = '';
                base.parentesco_responsavel = '';
                base.parentesco_outro = '';
            }
            return base;
        })
        .post(rotas.cadastro, {
            onFinish: () => form.reset('senha', 'senha_confirmation'),
        });
</script>

<template>
    <Head title="Criar conta" />
    <Aviso tipo="info">
        Guardamos só apelido, ano de nascimento, série, e-mail e suas respostas.
        Só você e o seu responsável (se você for menor de 18) veem o seu
        desempenho.
        <Link :href="rotas.privacidade" class="gb-link"
            >Ler tudo sobre privacidade</Link
        >
    </Aviso>
    <form class="gb-form" novalidate @submit.prevent="enviar">
        <Campo
            v-model="form.apelido"
            rotulo="Apelido"
            autocomplete="nickname"
            ajuda="Não precisa ser seu nome de verdade."
            :erro="form.errors.apelido"
            obrigatorio
        />
        <Campo
            v-model="form.email"
            rotulo="E-mail"
            tipo="email"
            autocomplete="email"
            :erro="form.errors.email"
            obrigatorio
        />
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
            rotulo="Ano de nascimento"
            tipo="number"
            :min="1900"
            :max="new Date().getFullYear()"
            autocomplete="bday-year"
            :erro="form.errors.ano_nascimento"
            obrigatorio
        />
        <ConviteNoCadastro
            v-if="convite_responsavel"
            v-model:confirma="form.confirma_responsavel"
            :convite="convite_responsavel"
            :erro="(form.errors as Record<string, string>).convite"
        />
        <ResponsavelNoCadastro
            v-if="pedeResponsavel"
            v-model:email="form.email_responsavel"
            v-model:parentesco="form.parentesco_responsavel"
            v-model:outro="form.parentesco_outro"
            :erro-email="form.errors.email_responsavel"
            :erro-parentesco="form.errors.parentesco_responsavel"
            :erro-outro="form.errors.parentesco_outro"
        />
        <GrupoOpcoes
            v-model="form.serie"
            legenda="Série"
            :opcoes="SERIES"
            :erro="form.errors.serie"
        />
        <EscolhaAvatar
            v-model="form.avatar"
            legenda="Escolha seu bicho (opcional)"
            ajuda="Se você não escolher, a gente sorteia um. Dá para trocar depois na página Conta."
            :erro="form.errors.avatar"
        />
        <div>
            <label class="gb-check">
                <input v-model="form.aceite_privacidade" type="checkbox" />
                <span>
                    Li e concordo com os
                    <Link :href="rotas.termos" class="gb-link"
                        >Termos de uso</Link
                    >
                    e a
                    <Link :href="rotas.privacidade" class="gb-link"
                        >política de privacidade</Link
                    >.
                </span>
            </label>
            <p
                v-if="form.errors.aceite_privacidade"
                class="gb-erro"
                role="alert"
            >
                {{ form.errors.aceite_privacidade }}
            </p>
        </div>
        <button
            type="submit"
            class="l-btn l-btn--orange"
            :disabled="form.processing"
        >
            Criar minha conta
        </button>
    </form>
    <p class="gb-texto">
        Já tem conta?
        <Link :href="rotas.entrar" class="gb-link">Entrar</Link>
    </p>
</template>
