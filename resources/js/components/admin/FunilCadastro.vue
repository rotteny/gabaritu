<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    etapas: { chave: string; rotulo: string; valor: number }[];
    menores: { cadastrados: number; autorizados: number };
}>();

const topo = computed(() => Math.max(1, ...props.etapas.map((e) => e.valor)));
const pct = (v: number) =>
    props.etapas[0]?.valor ? Math.round((v / props.etapas[0].valor) * 100) : 0;
</script>

<template>
    <ol v-if="etapas.some((e) => e.valor > 0)" class="ad-funil">
        <li v-for="e in etapas" :key="e.chave">
            <span class="ad-funil-txt">
                <span>{{ e.rotulo }}</span>
                <span class="ad-num"
                    >{{ e.valor.toLocaleString('pt-BR') }} ({{
                        pct(e.valor)
                    }}%)</span
                >
            </span>
            <span
                class="ad-funil-barra"
                :style="{ width: `${(e.valor / topo) * 100}%` }"
                aria-hidden="true"
            />
        </li>
    </ol>
    <p v-else class="ad-mudo">Sem cadastros no período.</p>
    <p class="ad-dica">
        Menores de 18: {{ menores.autorizados }} autorizados de
        {{ menores.cadastrados }} cadastrados.
    </p>
</template>
