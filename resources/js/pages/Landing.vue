<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import PeixotoNadador from '@/components/PeixotoNadador.vue';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { observarSecoes } from '@/lib/somInterface';
import BotaoSom from '@/components/BotaoSom.vue';
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

const reduzido = useMovimentoReduzido();
const heroVisivel = ref(true);
let parar = () => {};
let olhoHero: IntersectionObserver | null = null;
onMounted(() => {
    const peixeHero = document.querySelector('.l-hero-peixoto');

    if (peixeHero) {
        olhoHero = new IntersectionObserver(([e]) => {
            heroVisivel.value = e.isIntersecting;
        });
        olhoHero.observe(peixeHero);
    }

    parar = observarSecoes([
        ...document.querySelectorAll('main#conteudo > section'),
    ]);
});
onBeforeUnmount(() => {
    parar();
    olhoHero?.disconnect();
});
</script>

<template>
    <Head title="Gabaritu: estude pro ENEM com um baiacu">
        <meta
            name="description"
            content="Plataforma gratuita e open source de estudo pro ENEM, pra quem tem 13 anos ou mais. Acertou, vira festa. Errou, o Peixoto zoa a resposta e explica o porquê."
        />
    </Head>
    <div class="landing">
        <a href="#conteudo" class="l-skip">Pular para o conteúdo</a>
        <header class="l-header">
            <div class="l-wrap l-header-in"><BotaoSom /></div>
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
        <PeixotoNadador :oculto="heroVisivel" :reduzido="reduzido" />
        <LandingFooter
            :email-contato="emailContato"
            :repositorio-url="repositorioUrl"
        />
    </div>
</template>
