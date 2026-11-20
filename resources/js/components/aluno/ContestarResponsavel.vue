<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmarAcao from '@/components/responsavel/ConfirmarAcao.vue';
import { rotas } from '@/lib/rotas';

const props = defineProps<{ id: number; nome: string }>();

const aberto = ref(false);
const contestar = () =>
    router.post(
        rotas.contestarResponsavel(props.id),
        {},
        { preserveScroll: true, onFinish: () => (aberto.value = false) },
    );
</script>

<template>
    <div class="gb-contestar">
        <button
            v-if="!aberto"
            type="button"
            class="gb-link gb-link-botao"
            @click="aberto = true"
        >
            Essa pessoa não é meu responsável
        </button>
        <ConfirmarAcao
            v-else
            titulo="Essa pessoa não é seu responsável?"
            :texto="`O acesso de ${nome} é suspenso na hora, e os outros responsáveis (e o Gabaritu) são avisados para conferir.`"
            rotulo-confirmar="Sim, suspender o acesso"
            @confirmar="contestar"
            @cancelar="aberto = false"
        />
    </div>
</template>
