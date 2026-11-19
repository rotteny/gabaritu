<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LandingDemo from '@/components/landing/LandingDemo.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingLevels from '@/components/landing/LandingLevels.vue';
import LandingOpenSource from '@/components/landing/LandingOpenSource.vue';
import LandingPrivacy from '@/components/landing/LandingPrivacy.vue';
import LandingSteps from '@/components/landing/LandingSteps.vue';
import LandingSubjects from '@/components/landing/LandingSubjects.vue';
import LandingTracking from '@/components/landing/LandingTracking.vue';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/landing/sections.css';

defineProps<{
    cadastroAberto: boolean;
    emailContato: string;
    repositorioUrl: string;
}>();
</script>

<template>
    <Head title="Estude pro ENEM com um baiacu que zoa a sua resposta">
        <meta
            name="description"
            content="Plataforma gratuita e open source de estudo pro ENEM, pra quem tem 13 anos ou mais. Acertou, vira festa. Errou, o Peixoto zoa a resposta e explica o porquê."
        />
    </Head>
    <div class="landing">
        <a href="#conteudo" class="l-skip">Pular para o conteúdo</a>
        <header class="l-header">
            <div class="l-wrap"><span class="l-logo">Gabaritu</span></div>
        </header>
        <main id="conteudo">
            <LandingHero
                :cadastro-aberto="cadastroAberto"
                :repositorio-url="repositorioUrl"
            />
            <LandingDemo />
            <LandingSteps />
            <LandingSubjects />
            <LandingLevels />
            <LandingTracking />
            <LandingPrivacy />
            <LandingOpenSource :repositorio-url="repositorioUrl" />
        </main>
        <LandingFooter :email-contato="emailContato" />
    </div>
</template>
