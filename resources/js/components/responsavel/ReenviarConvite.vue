<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useContagem } from '@/composables/useContagem';
import { rotas } from '@/lib/rotas';

const props = defineProps<{ restantes: number }>();
const form = useForm({});
const { restante, iniciar } = useContagem();

const bloqueado = computed(
    () => props.restantes <= 0 || restante.value > 0 || form.processing,
);
const reenviar = () =>
    form.post(rotas.reenviarConvite, {
        preserveScroll: true,
        onSuccess: () => iniciar(30),
    });
</script>

<template>
    <div class="gb-bloco">
        <h3>O e-mail não chegou?</h3>
        <p class="gb-texto">
            Olhe a caixa de spam. Se não estiver lá, mande de novo.
        </p>
        <div class="gb-acoes">
            <button
                type="button"
                class="l-btn l-btn--yellow"
                :disabled="bloqueado"
                @click="reenviar"
            >
                <template v-if="restante > 0">
                    Reenviar de novo em
                    <span class="gb-contagem">{{ restante }}s</span>
                </template>
                <template v-else>Reenviar o convite</template>
            </button>
        </div>
        <p class="gb-miudo" role="status">
            <template v-if="restantes > 0">
                Você ainda pode reenviar {{ restantes }}
                {{ restantes === 1 ? 'vez' : 'vezes' }} hoje.
            </template>
            <template v-else>
                Você já reenviou 3 vezes hoje. Tente de novo amanhã.
            </template>
        </p>
    </div>
</template>
