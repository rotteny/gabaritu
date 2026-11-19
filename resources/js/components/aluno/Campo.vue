<script setup lang="ts">
import { EyeIcon, EyeOffIcon } from '@lucide/vue';
import { computed, ref, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        rotulo: string;
        tipo?: 'text' | 'email' | 'password' | 'number';
        erro?: string;
        ajuda?: string;
        autocomplete?: string;
        min?: number;
        max?: number;
        obrigatorio?: boolean;
    }>(),
    { tipo: 'text' },
);

const modelo = defineModel<string | number>({ required: true });
const id = useId();
const mostrar = ref(false);
const tipoReal = computed(() =>
    props.tipo === 'password' && mostrar.value ? 'text' : props.tipo,
);
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
            <input
                :id="id"
                v-model="modelo"
                :type="tipoReal"
                :autocomplete="autocomplete"
                :min="min"
                :max="max"
                :required="obrigatorio"
                :aria-invalid="erro ? 'true' : undefined"
                :aria-describedby="descricao"
            />
            <button
                v-if="tipo === 'password'"
                type="button"
                :aria-pressed="mostrar"
                :aria-label="mostrar ? 'Esconder a senha' : 'Mostrar a senha'"
                @click="mostrar = !mostrar"
            >
                <EyeOffIcon v-if="mostrar" :size="20" aria-hidden="true" />
                <EyeIcon v-else :size="20" aria-hidden="true" />
            </button>
        </div>
        <p v-if="ajuda" :id="`${id}-ajuda`" class="gb-ajuda">{{ ajuda }}</p>
        <p v-if="erro" :id="`${id}-erro`" class="gb-erro" role="alert">
            {{ erro }}
        </p>
    </div>
</template>
