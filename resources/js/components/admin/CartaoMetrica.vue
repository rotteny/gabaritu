<script setup lang="ts">
defineProps<{ rotulo: string; valor: number | string; detalhe?: string }>();
</script>

<template>
    <dl class="ad-cartao ad-metrica">
        <dt>{{ rotulo }}</dt>
        <dd>
            {{
                typeof valor === 'number'
                    ? valor.toLocaleString('pt-BR')
                    : valor
            }}
        </dd>
        <small v-if="detalhe">{{ detalhe }}</small>
    </dl>
</template>
