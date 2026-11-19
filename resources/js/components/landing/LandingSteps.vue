<script setup lang="ts">
import LandingSection from './LandingSection.vue';

const passos = [
    {
        titulo: 'Escolha a matéria',
        texto: 'São 14. Sim, até Educação Física. Não, o baiacu não vai te mandar correr.',
    },
    {
        titulo: 'Escolha a zoeira',
        texto: 'Fofo, Sarcástico ou Sem filtro. Você regula, o Peixoto obedece (quase sempre).',
    },
    {
        titulo: 'Responda e aprenda',
        texto: 'Acertou, vira festa. Errou, vira piada na resposta e, logo depois, uma explicação curta do porquê.',
    },
];
</script>

<template>
    <LandingSection id="como-funciona" titulo="Como funciona, em 3 passos">
        <ol class="l-grid l-grid--3">
            <li v-for="(p, i) in passos" :key="p.titulo" class="l-card l-step">
                <span class="l-step-num" aria-hidden="true">{{ i + 1 }}</span>
                <h3 class="l-h3">{{ p.titulo }}</h3>
                <p>{{ p.texto }}</p>
            </li>
        </ol>
    </LandingSection>
</template>
