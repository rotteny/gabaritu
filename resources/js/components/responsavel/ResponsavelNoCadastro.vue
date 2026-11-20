<script setup lang="ts">
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';

defineProps<{
    erroEmail?: string;
    erroParentesco?: string;
    erroOutro?: string;
}>();

const email = defineModel<string>('email', { required: true });
const parentesco = defineModel<string>('parentesco', { required: true });
const outro = defineModel<string>('outro', { required: true });
</script>

<template>
    <fieldset class="gb-form gb-fieldset-resp">
        <legend class="gb-legenda">Seu responsável</legend>
        <Aviso tipo="info">
            Quem tem menos de 18 anos precisa da autorização de um responsável
            (pai, mãe, avó/avô ou tutor), por lei. Enviamos um convite para esse
            e-mail e você só começa depois que ele autorizar.
        </Aviso>
        <Campo
            v-model="email"
            rotulo="E-mail do seu responsável"
            tipo="email"
            autocomplete="off"
            ajuda="Tem de ser diferente do seu e-mail."
            :erro="erroEmail"
            obrigatorio
        />
        <CampoParentesco
            v-model:parentesco="parentesco"
            v-model:outro="outro"
            rotulo="Parentesco dele com você"
            :erro-parentesco="erroParentesco"
            :erro-outro="erroOutro"
        />
    </fieldset>
</template>
