<script setup lang="ts">
import { ref, useId } from 'vue';
import Campo from '@/components/aluno/Campo.vue';

const props = defineProps<{
    titulo: string;
    texto: string;
    rotuloConfirmar: string;
    pedeSenha?: boolean;
    erroSenha?: string;
    processando?: boolean;
}>();
const emit = defineEmits<{ confirmar: [senha: string]; cancelar: [] }>();

const senha = ref('');
const id = useId();
const confirmar = () => emit('confirmar', senha.value);
</script>

<template>
    <form
        class="gb-confirma gb-form"
        novalidate
        role="alertdialog"
        :aria-labelledby="`${id}-t`"
        :aria-describedby="`${id}-d`"
        @submit.prevent="confirmar"
        @keydown.esc="emit('cancelar')"
    >
        <h4 :id="`${id}-t`" class="gb-subtitulo">{{ titulo }}</h4>
        <p :id="`${id}-d`" class="gb-texto">{{ texto }}</p>
        <Campo
            v-if="pedeSenha"
            v-model="senha"
            rotulo="Digite a sua senha para confirmar"
            tipo="password"
            autocomplete="current-password"
            :erro="erroSenha"
            obrigatorio
        />
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--perigo gb-btn-mini"
                :disabled="processando || (props.pedeSenha && !senha)"
            >
                {{ rotuloConfirmar }}
            </button>
            <button
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                @click="emit('cancelar')"
            >
                Cancelar
            </button>
        </div>
    </form>
</template>
