<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import GrupoOpcoes from '@/components/aluno/GrupoOpcoes.vue';
import { rotas } from '@/lib/rotas';

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

const form = useForm({
    apelido: '',
    email: '',
    senha: '',
    senha_confirmation: '',
    ano_nascimento: '' as string | number,
    serie: '2em',
    aceite_privacidade: false,
});

const enviar = () =>
    form.post(rotas.cadastro, {
        onFinish: () => form.reset('senha', 'senha_confirmation'),
    });
</script>

<template>
    <Head title="Criar conta" />
    <Aviso tipo="info">
        Guardamos só apelido, ano de nascimento, série, e-mail e suas respostas.
        Ninguém além de você vê o seu desempenho.
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
        <GrupoOpcoes
            v-model="form.serie"
            legenda="Série"
            :opcoes="SERIES"
            :erro="form.errors.serie"
        />
        <div>
            <label class="gb-check">
                <input v-model="form.aceite_privacidade" type="checkbox" />
                <span>
                    Li e concordo com a
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
