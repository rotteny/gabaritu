<script setup lang="ts">
import FundoAquario from '@/components/aluno/FundoAquario.vue';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { useTelaFixa } from '@/composables/useTelaFixa';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/aluno/base.css';
import '../../css/aluno/questao.css';

useTelaFixa();
const reduzido = useMovimentoReduzido();
</script>

<template>
    <div class="gb gb-questao" :data-reduzir="reduzido">
        <FundoAquario baixa />
        <slot />
    </div>
</template>
