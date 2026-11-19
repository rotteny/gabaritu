<script setup lang="ts">
import { alternativasDemo, niveisZoeira } from '@/data/landingDemo';
import LandingSection from './LandingSection.vue';

const erro = alternativasDemo[0];
</script>

<template>
    <LandingSection
        id="zoeira"
        titulo="Os níveis de zoeira"
        lead="O mesmo erro gera piadas diferentes pra pessoas diferentes. Olha a mesma resposta, 'Ribossomo', em cada nível:"
    >
        <ul class="l-grid l-grid--3">
            <li
                v-for="n in niveisZoeira"
                :key="n.id"
                class="l-card l-level"
                :class="`l-level--${n.id}`"
            >
                <h3 class="l-h3">{{ n.nome }}</h3>
                <p class="l-level-desc">{{ n.descricao }}</p>
                <blockquote class="l-speech l-speech--static">
                    {{ erro.piadas?.[n.id] }}
                </blockquote>
            </li>
        </ul>
    </LandingSection>
</template>
