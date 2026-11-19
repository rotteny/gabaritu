<script setup lang="ts">
import LandingBubbles from './LandingBubbles.vue';
import LandingCta from './LandingCta.vue';
import PeixotoSvg from './PeixotoSvg.vue';

defineProps<{ cadastroAberto: boolean; repositorioUrl: string }>();
</script>

<template>
    <section class="l-hero" aria-labelledby="titulo-hero">
        <LandingBubbles />
        <div class="l-wrap l-hero-grid">
            <div class="l-board">
                <p class="l-board-tag">Estudo pro ENEM, versão aquário</p>
                <h1 id="titulo-hero" class="l-board-title">
                    Estude pro ENEM com um baiacu que zoa a sua resposta.
                </h1>
                <p class="l-board-text">
                    O Gabaritu é uma plataforma gratuita e open source de estudo
                    pro ENEM, pra quem tem 13 anos ou mais. Sem anúncio e sem
                    pegadinha.
                </p>
                <LandingCta
                    :cadastro-aberto="cadastroAberto"
                    :repositorio-url="repositorioUrl"
                />
            </div>
            <div class="l-hero-fish">
                <p class="l-speech">
                    Calma: eu zoo a resposta, nunca você. Você continua sendo
                    gente fina.
                </p>
                <div class="l-float l-hero-peixoto"><PeixotoSvg /></div>
                <svg class="l-exam" viewBox="0 0 40 52" aria-hidden="true">
                    <path d="M3 4 L35 2 L38 47 L5 50 Z" />
                    <path d="M10 14 H30 M10 22 H30 M10 30 H24" />
                    <text x="22" y="46" class="l-exam-grade">3,5</text>
                </svg>
            </div>
        </div>
    </section>
</template>
