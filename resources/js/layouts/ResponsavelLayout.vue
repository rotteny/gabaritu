<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BotaoSair from '@/components/aluno/BotaoSair.vue';
import FundoAquario from '@/components/aluno/FundoAquario.vue';
import LinhaCredito from '@/components/LinhaCredito.vue';
import MarcaGabaritu from '@/components/MarcaGabaritu.vue';
import { useAluno } from '@/composables/useAluno';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { REPOSITORIO_PADRAO } from '@/lib/constantes';
import { rotas } from '@/lib/rotas';
import '../../css/landing/base.css';
import '../../css/landing/peixoto.css';
import '../../css/aluno/base.css';
import '../../css/aluno/paginas.css';
import '../../css/aluno/responsavel.css';

const { responsavel, gabaritu } = useAluno();
const reduzido = useMovimentoReduzido();
const page = usePage();
const repositorioUrl = computed(
    () => gabaritu.value?.repositorio_url ?? REPOSITORIO_PADRAO,
);
const estreita = computed(
    () => !['responsavel/Painel', 'responsavel/Aluno'].includes(page.component),
);
const logado = computed(() => responsavel.value !== null);

const itens = [
    { rotulo: 'Meus alunos', href: rotas.painel },
    { rotulo: 'Conta', href: rotas.contaResponsavel },
];
const caminho = computed(() => page.url.split('?')[0].replace('/_previa', ''));
const ativo = (href: string) =>
    caminho.value === href ||
    (href === rotas.painel && caminho.value.startsWith('/responsavel/alunos'));
</script>

<template>
    <div class="gb" :data-reduzir="reduzido">
        <FundoAquario />
        <a href="#conteudo" class="l-skip">Pular para o conteúdo</a>
        <header class="gb-topo gb-topo--resp">
            <div class="gb-topo-in">
                <Link
                    :href="logado ? rotas.painel : rotas.landing"
                    class="gb-logo"
                    aria-label="Gabaritu"
                >
                    <MarcaGabaritu tamanho="sm" />
                </Link>
                <nav v-if="logado" class="gb-nav" aria-label="Principal">
                    <Link
                        v-for="item in itens"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="ativo(item.href) ? 'page' : undefined"
                    >
                        {{ item.rotulo }}
                    </Link>
                </nav>
                <BotaoSair v-if="logado" compacto />
            </div>
        </header>
        <main
            id="conteudo"
            :class="{ 'gb-pagina--estreita': estreita }"
            class="gb-pagina"
        >
            <slot />
        </main>
        <LinhaCredito :repositorio-url="repositorioUrl" />
    </div>
</template>
