<script setup lang="ts">
import Campo from '@/components/aluno/Campo.vue';
import CampoSelecao from '@/components/aluno/CampoSelecao.vue';
import { PARENTESCOS } from '@/lib/responsavel';

defineProps<{
    rotulo?: string;
    ajuda?: string;
    erroParentesco?: string;
    erroOutro?: string;
}>();

const parentesco = defineModel<string>('parentesco', { required: true });
const outro = defineModel<string>('outro', { required: true });
</script>

<template>
    <CampoSelecao
        v-model="parentesco"
        :rotulo="rotulo ?? 'Parentesco'"
        :opcoes="PARENTESCOS"
        :ajuda="ajuda"
        :erro="erroParentesco"
    />
    <Campo
        v-if="parentesco === 'outro'"
        v-model="outro"
        rotulo="Qual parentesco?"
        ajuda="Texto curto, por exemplo: tio, padrasto."
        :erro="erroOutro"
        obrigatorio
    />
</template>
