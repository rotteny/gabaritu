<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BotaoSom from '@/components/BotaoSom.vue';
import LinhaCredito from '@/components/LinhaCredito.vue';
import MarcaGabaritu from '@/components/MarcaGabaritu.vue';
import FundoAquario from '@/components/aluno/FundoAquario.vue';
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { useAluno } from '@/composables/useAluno';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { REPOSITORIO_PADRAO } from '@/lib/constantes';
import { rotas } from '@/lib/rotas';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/aluno/base.css';
import '../../css/aluno/paginas.css';
import '../../css/aluno/responsavel.css';

defineProps<{ title?: string; description?: string }>();

const reduzido = useMovimentoReduzido();
const { gabaritu } = useAluno();
const repositorioUrl = computed(
    () => gabaritu.value?.repositorio_url ?? REPOSITORIO_PADRAO,
);
</script>

<template>
    <div class="gb" :data-reduzir="reduzido">
        <FundoAquario />
        <main class="gb-auth">
            <div class="gb-auth-topo">
                <Link
                    :href="rotas.landing"
                    class="gb-logo"
                    aria-label="Gabaritu"
                >
                    <MarcaGabaritu tamanho="md" />
                </Link>
                <div class="gb-auth-peixe" aria-hidden="true">
                    <PeixotoSvg />
                </div>
                <BotaoSom />
            </div>
            <section class="gb-cartao gb-auth-cartao">
                <h1 v-if="title" class="gb-titulo gb-titulo--cartao">
                    {{ title }}
                </h1>
                <p v-if="description" class="gb-texto">{{ description }}</p>
                <slot />
            </section>
        </main>
        <footer class="gb-rodape">
            <LinhaCredito :repositorio-url="repositorioUrl" />
        </footer>
    </div>
</template>
