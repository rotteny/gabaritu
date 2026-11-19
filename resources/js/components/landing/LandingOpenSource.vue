<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';
import LandingSection from './LandingSection.vue';

defineProps<{ repositorioUrl: string }>();
</script>

<template>
    <LandingSection
        id="open-source"
        titulo="Código aberto, de ponta a ponta"
        lead="O código é MIT e o conteúdo é CC BY-SA. Pode ler, copiar, ajudar e mandar correção. Até do gabarito."
        escura
    >
        <a
            :href="repositorioUrl"
            class="l-btn l-btn--yellow"
            target="_blank"
            rel="noopener noreferrer"
        >
            github.com/rotteny/gabaritu
            <ExternalLink class="size-5" aria-hidden="true" />
            <span class="sr-only">(abre em nova aba)</span>
        </a>
    </LandingSection>
</template>
