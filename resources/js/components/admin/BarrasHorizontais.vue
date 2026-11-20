<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    itens: { rotulo: string; valor: number; texto?: string }[];
    unidade?: string;
}>();

const maximo = computed(() => Math.max(1, ...props.itens.map((i) => i.valor)));
</script>

<template>
    <ul class="ad-barras">
        <li v-for="i in itens" :key="i.rotulo" class="ad-barra-linha">
            <span>{{ i.rotulo }}</span>
            <span class="ad-barra-trilho" aria-hidden="true">
                <span :style="{ width: `${(i.valor / maximo) * 100}%` }" />
            </span>
            <span class="ad-num"
                ><strong>{{
                    i.texto ?? i.valor.toLocaleString('pt-BR')
                }}</strong></span
            >
        </li>
    </ul>
</template>
