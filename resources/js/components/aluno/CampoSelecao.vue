<script setup lang="ts">
import { computed, useId } from 'vue';

const props = defineProps<{
    rotulo: string;
    opcoes: { valor: string; rotulo: string }[];
    erro?: string;
    ajuda?: string;
}>();

const modelo = defineModel<string>({ required: true });
const id = useId();
const descricao = computed(
    () =>
        [props.ajuda ? `${id}-ajuda` : '', props.erro ? `${id}-erro` : '']
            .filter(Boolean)
            .join(' ') || undefined,
);
</script>

<template>
    <div class="gb-campo">
        <label :for="id">{{ rotulo }}</label>
        <div class="gb-entrada">
            <select
                :id="id"
                v-model="modelo"
                :aria-invalid="erro ? 'true' : undefined"
                :aria-describedby="descricao"
            >
                <option v-for="o in opcoes" :key="o.valor" :value="o.valor">
                    {{ o.rotulo }}
                </option>
            </select>
        </div>
        <p v-if="ajuda" :id="`${id}-ajuda`" class="gb-ajuda">{{ ajuda }}</p>
        <p v-if="erro" :id="`${id}-erro`" class="gb-erro" role="alert">
            {{ erro }}
        </p>
    </div>
</template>
