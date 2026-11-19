<script setup lang="ts">
import LandingSection from './LandingSection.vue';

const regras = [
    {
        titulo: 'Idade',
        texto: 'A partir de 13 anos. O nível Sem filtro só a partir de 15.',
    },
    {
        titulo: 'Dados mínimos',
        texto: 'Só apelido, ano de nascimento, série e e-mail. Nada além disso.',
    },
    {
        titulo: 'Apagou, sumiu',
        texto: 'Apagar a conta apaga tudo, de verdade.',
    },
    {
        titulo: 'Grátis e sem anúncio',
        texto: 'Sem mensalidade, sem propaganda, sem pegadinha.',
    },
];
</script>

<template>
    <LandingSection
        id="privacidade"
        titulo="Idade e privacidade, sem enrolação"
    >
        <dl class="l-grid l-grid--2 l-facts">
            <div v-for="r in regras" :key="r.titulo" class="l-card">
                <dt class="l-h3">{{ r.titulo }}</dt>
                <dd class="m-0">{{ r.texto }}</dd>
            </div>
        </dl>
        <p class="l-aside">
            O Peixoto não vende seus dados. Ele nem tem bolso.
        </p>
    </LandingSection>
</template>
