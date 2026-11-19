<script setup lang="ts">
import { useId } from 'vue';

defineProps<{
    legenda: string;
    opcoes: { valor: string; rotulo: string }[];
    erro?: string;
}>();

const modelo = defineModel<string>({ required: true });
const nome = useId();
</script>

<template>
    <fieldset class="gb-grupo">
        <legend class="gb-legenda">{{ legenda }}</legend>
        <div class="gb-grupo-opcoes">
            <label v-for="o in opcoes" :key="o.valor" class="l-pill">
                <input
                    v-model="modelo"
                    type="radio"
                    :name="nome"
                    :value="o.valor"
                />
                <span>{{ o.rotulo }}</span>
            </label>
        </div>
        <p v-if="erro" class="gb-erro" role="alert">{{ erro }}</p>
    </fieldset>
</template>
