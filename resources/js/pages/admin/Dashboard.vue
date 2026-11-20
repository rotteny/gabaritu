<script setup lang="ts">
import { computed } from 'vue';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import CartaoMetrica from '@/components/admin/CartaoMetrica.vue';
import FunilCadastro from '@/components/admin/FunilCadastro.vue';
import GraficoSeries from '@/components/admin/GraficoSeries.vue';
import SeletorPeriodo from '@/components/admin/SeletorPeriodo.vue';
import { useAdmin } from '@/composables/useAdmin';
import type { DashboardProps } from '@/types/admin';

const props = defineProps<DashboardProps>();
const { admin } = useAdmin();

const cor = [
    'var(--ad-serie-1)',
    'var(--ad-serie-2)',
    'var(--ad-serie-3)',
    'var(--ad-serie-4)',
];
const serieCadastros = [
    { chave: 'cadastros', rotulo: 'Cadastros', cor: cor[0] },
];
const serieUso = [
    { chave: 'alunos_ativos', rotulo: 'Alunos ativos', cor: cor[1] },
    { chave: 'logins', rotulo: 'Logins', cor: cor[2] },
    { chave: 'respostas', rotulo: 'Respostas', cor: cor[0] },
];
const serieVisitas = [
    {
        chave: 'visitas_landing',
        rotulo: 'Visitas à landing (inclui robôs)',
        cor: cor[3],
    },
];
const dados = computed(() => props.series);
const c = props.cartoes;
</script>

<template>
    <CabecalhoPagina
        titulo="Dashboard"
        subtitulo="Números agregados. Nada aqui identifica um aluno."
    >
        <SeletorPeriodo
            v-if="admin"
            :periodo="periodo"
            :base="admin.rotas.dashboard"
        />
        <template v-if="admin">
            <a
                :href="admin.exportar.metricas"
                download
                class="ad-btn ad-btn--claro"
                >CSV métricas</a
            >
            <a
                :href="admin.exportar.cadastros"
                download
                class="ad-btn ad-btn--claro"
                >CSV cadastros</a
            >
        </template>
    </CabecalhoPagina>

    <section class="ad-grade" aria-label="Resumo">
        <CartaoMetrica
            rotulo="Alunos"
            :valor="c.alunos.total"
            :detalhe="`${c.alunos.por_status.ativo} ativos · ${c.alunos.por_status.aguardando_responsavel} aguardando responsável`"
        />
        <CartaoMetrica rotulo="Responsáveis" :valor="c.responsaveis" />
        <CartaoMetrica
            rotulo="Cadastros hoje"
            :valor="c.cadastros.hoje"
            :detalhe="`${c.cadastros.ultimos_7_dias} em 7 dias · ${c.cadastros.ultimos_30_dias} em 30 dias`"
        />
        <CartaoMetrica
            rotulo="Ativos hoje"
            :valor="c.ativos.hoje"
            :detalhe="`${c.ativos.ultimos_7_dias} em 7 dias`"
        />
        <CartaoMetrica rotulo="Respostas hoje" :valor="c.respostas_hoje" />
    </section>

    <section class="ad-grade ad-grade--2">
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Cadastros por dia</h2>
            <GraficoSeries
                titulo="Cadastros por dia"
                :dados="dados"
                :series="serieCadastros"
                tipo="barra"
            />
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Uso por dia</h2>
            <GraficoSeries
                titulo="Alunos ativos, logins e respostas por dia"
                :dados="dados"
                :series="serieUso"
            />
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Funil</h2>
            <FunilCadastro :etapas="funil.etapas" :menores="funil.menores" />
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Visitas à landing</h2>
            <GraficoSeries
                titulo="Visitas à landing por dia"
                :dados="dados"
                :series="serieVisitas"
            />
        </article>
    </section>
</template>
