<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import GrupoOpcoes from '@/components/aluno/GrupoOpcoes.vue';
import { rotas } from '@/lib/rotas';
import type { Zoeira } from '@/types/gabaritu';

const props = defineProps<{ alunoId: number; teto: Zoeira }>();
const valor = ref<string>(props.teto);
watch(
    () => props.teto,
    (t) => (valor.value = t),
);

const OPCOES = [
    { valor: 'fofo', rotulo: 'Só fofo' },
    { valor: 'sarc', rotulo: 'Até sarcástico' },
    { valor: 'sem', rotulo: 'Sem limite meu' },
];

const salvar = (novo: string) =>
    router.patch(
        rotas.zoeiraAluno(props.alunoId),
        { teto: novo },
        { preserveScroll: true },
    );
watch(valor, (novo) => {
    if (novo !== props.teto) salvar(novo);
});
</script>

<template>
    <div class="gb-bloco">
        <GrupoOpcoes
            v-model="valor"
            legenda="Teto de zoeira do Peixoto"
            :opcoes="OPCOES"
        />
        <p class="gb-miudo">
            O Peixoto brinca com os erros do aluno. Vale o menor entre o teto da
            idade e o seu.
        </p>
    </div>
</template>
