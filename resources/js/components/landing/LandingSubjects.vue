<script setup lang="ts">
import { areasMaterias } from '@/data/landingMaterias';
import LandingSection from './LandingSection.vue';
</script>

<template>
    <LandingSection
        id="materias"
        titulo="As 14 matérias"
        lead="Todas as objetivas do ENEM. Você escolhe por onde começar."
    >
        <div class="l-grid l-grid--2">
            <article
                v-for="a in areasMaterias"
                :key="a.area"
                class="l-card"
                :class="`l-tone--${a.cor}`"
            >
                <h3 class="l-h3">{{ a.area }}</h3>
                <ul class="l-chips">
                    <li v-for="m in a.materias" :key="m" class="l-chip">
                        {{ m }}
                    </li>
                </ul>
            </article>
        </div>
    </LandingSection>
</template>
