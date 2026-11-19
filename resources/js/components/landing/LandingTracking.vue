<script setup lang="ts">
import { BarChart3, Repeat2, Target } from '@lucide/vue';
import LandingSection from './LandingSection.vue';

const itens = [
    {
        icone: BarChart3,
        titulo: 'Nota estimada por assunto',
        texto: 'Você vê onde está forte e onde está fraco, assunto por assunto.',
    },
    {
        icone: Target,
        titulo: 'O erro que mais aparece',
        texto: 'Conta, conceito ou interpretação? O Gabaritu mostra qual tropeço se repete.',
    },
    {
        icone: Repeat2,
        titulo: 'Revisão das erradas',
        texto: 'As questões que você errou voltam pra você refazer, até virar acerto.',
    },
];
</script>

<template>
    <LandingSection id="acompanhamento" titulo="O que você acompanha">
        <ul class="l-grid l-grid--3">
            <li v-for="i in itens" :key="i.titulo" class="l-card">
                <component :is="i.icone" class="l-icon" aria-hidden="true" />
                <h3 class="l-h3">{{ i.titulo }}</h3>
                <p>{{ i.texto }}</p>
            </li>
        </ul>
        <p class="l-card l-note">
            <strong>Não é joguinho.</strong> Não tem fases, estrelas nem combo.
            É estudo de verdade, só que com efeitos de desenho animado.
        </p>
    </LandingSection>
</template>
