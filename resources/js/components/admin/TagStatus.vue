<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ valor: string | null }>();
const rotulos: Record<string, string> = {
    ativo: 'Ativo',
    aguardando_responsavel: 'Aguardando responsável',
    suspenso: 'Suspenso',
    pendente: 'Pendente',
    liberada: 'Liberada',
    remover: 'Remover',
    aberto: 'Aberto',
    resolvido: 'Resolvido',
    descartado: 'Descartado',
};
const classe = computed(() =>
    ['ativo', 'liberada', 'resolvido'].includes(props.valor ?? '')
        ? 'ad-tag--ok'
        : ['remover', 'suspenso'].includes(props.valor ?? '')
          ? 'ad-tag--ruim'
          : ['aguardando_responsavel', 'pendente', 'aberto'].includes(
                  props.valor ?? '',
              )
            ? 'ad-tag--aviso'
            : '',
);
</script>

<template>
    <span v-if="valor" class="ad-tag" :class="classe">{{
        rotulos[valor] ?? valor
    }}</span>
</template>
