<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { rotas } from '@/lib/rotas';

defineProps<{ vencido: boolean }>();
</script>

<template>
    <section class="gb-cartao gb-prosa" aria-labelledby="convite-invalido">
        <h1 id="convite-invalido" class="gb-titulo gb-titulo--cartao">
            {{ vencido ? 'Este convite venceu' : 'Convite não encontrado' }}
        </h1>
        <p class="gb-texto">
            <template v-if="vencido">
                Os convites valem por 7 dias. Peça para o aluno reenviar um
                convite novo, pela tela dele no Gabaritu.
            </template>
            <template v-else>
                O link pode ter sido usado, cancelado ou copiado errado. Peça
                para o aluno enviar o convite de novo.
            </template>
        </p>
        <div class="gb-acoes">
            <Link :href="rotas.landing" class="l-btn l-btn--white"
                >Ir para o início</Link
            >
        </div>
    </section>
</template>
