<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FundoAquario from '@/components/aluno/FundoAquario.vue';
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { rotas } from '@/lib/rotas';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/aluno/base.css';
import '../../css/aluno/paginas.css';

defineProps<{ title?: string; description?: string }>();

const reduzido = useMovimentoReduzido();
</script>

<template>
    <div class="gb" :data-reduzir="reduzido">
        <FundoAquario />
        <main class="gb-auth">
            <div class="gb-auth-topo">
                <Link :href="rotas.landing" class="gb-logo">Gabaritu</Link>
                <div class="gb-auth-peixe" aria-hidden="true">
                    <PeixotoSvg />
                </div>
            </div>
            <section class="gb-cartao gb-auth-cartao">
                <h1 v-if="title" class="gb-titulo gb-titulo--cartao">
                    {{ title }}
                </h1>
                <p v-if="description" class="gb-texto">{{ description }}</p>
                <slot />
            </section>
        </main>
    </div>
</template>
