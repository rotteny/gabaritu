<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FundoAquario from '@/components/aluno/FundoAquario.vue';
import { useAluno } from '@/composables/useAluno';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { rotas } from '@/lib/rotas';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/aluno/base.css';
import '../../css/aluno/paginas.css';

const { aluno } = useAluno();
const reduzido = useMovimentoReduzido();
const page = usePage();

const itens = [
    { rotulo: 'Início', href: rotas.inicio },
    { rotulo: 'Desempenho', href: rotas.desempenho },
    { rotulo: 'Conta', href: rotas.conta },
];

const caminho = computed(() => page.url.split('?')[0]);
const ativo = (href: string) =>
    caminho.value === href ||
    (href === rotas.inicio && caminho.value.startsWith('/materias')) ||
    caminho.value === `/_previa${href}`;
</script>

<template>
    <div class="gb" :data-reduzir="reduzido">
        <FundoAquario />
        <a href="#conteudo" class="l-skip">Pular para o conteúdo</a>
        <header class="gb-topo">
            <div class="gb-topo-in">
                <Link
                    :href="aluno ? rotas.inicio : rotas.landing"
                    class="gb-logo"
                >
                    Gabaritu
                </Link>
                <nav v-if="aluno" class="gb-nav" aria-label="Principal">
                    <Link
                        v-for="item in itens"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="ativo(item.href) ? 'page' : undefined"
                    >
                        {{ item.rotulo }}
                    </Link>
                </nav>
                <nav v-else class="gb-nav" aria-label="Principal">
                    <Link :href="rotas.entrar">Entrar</Link>
                </nav>
            </div>
        </header>
        <main id="conteudo" class="gb-pagina">
            <slot />
        </main>
    </div>
</template>
