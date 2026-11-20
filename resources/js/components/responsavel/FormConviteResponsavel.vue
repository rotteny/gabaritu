<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Campo from '@/components/aluno/Campo.vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';
import { rotas } from '@/lib/rotas';

const props = defineProps<{
    alunoId: number;
    tipo: 'coresponsavel' | 'transferencia';
}>();
const emit = defineEmits<{ fechar: [] }>();

const form = useForm({
    email: '',
    parentesco: 'mae',
    parentesco_outro: '',
    tipo: props.tipo,
});
const enviar = () =>
    form.post(rotas.convitesAluno(props.alunoId), {
        preserveScroll: true,
        onSuccess: () => emit('fechar'),
    });
</script>

<template>
    <form class="gb-confirma gb-form" novalidate @submit.prevent="enviar">
        <p class="gb-texto">
            <template v-if="tipo === 'transferencia'">
                Você continua responsável até a outra pessoa aceitar. Só então o
                seu vínculo é encerrado.
            </template>
            <template v-else>
                A outra pessoa recebe um convite e passa a acompanhar o aluno
                junto com você.
            </template>
        </p>
        <Campo
            v-model="form.email"
            rotulo="E-mail da outra pessoa"
            tipo="email"
            autocomplete="off"
            :erro="form.errors.email"
            obrigatorio
        />
        <CampoParentesco
            v-model:parentesco="form.parentesco"
            v-model:outro="form.parentesco_outro"
            rotulo="Parentesco dela com o aluno"
            :erro-parentesco="form.errors.parentesco"
            :erro-outro="form.errors.parentesco_outro"
        />
        <div class="gb-acoes">
            <button
                type="submit"
                class="l-btn l-btn--orange gb-btn-mini"
                :disabled="form.processing"
            >
                Enviar convite
            </button>
            <button
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                @click="emit('fechar')"
            >
                Cancelar
            </button>
        </div>
    </form>
</template>
