<script setup lang="ts">
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import BarrasHorizontais from '@/components/admin/BarrasHorizontais.vue';
import CartaoMetrica from '@/components/admin/CartaoMetrica.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import GraficoSeries from '@/components/admin/GraficoSeries.vue';
import SeletorPeriodo from '@/components/admin/SeletorPeriodo.vue';
import { useAdmin } from '@/composables/useAdmin';
import type { Periodo } from '@/types/admin';

defineProps<{
    periodo: Periodo;
    serie: { dia: string; respostas: number }[];
    materias: {
        id: number;
        nome: string;
        respostas: number;
        pct_acerto: number | null;
    }[];
    questionarios: {
        id: number;
        titulo: string;
        assunto: string;
        tentativas: number;
        concluidas: number;
    }[];
    abandono: {
        iniciadas: number;
        concluidas: number;
        abandonadas: number;
        taxa_pct: number | null;
    };
    etiquetas_de_erro: {
        id: number;
        nome: string;
        materia: string;
        total: number;
    }[];
}>();
const { admin } = useAdmin();
const serieResp = [
    { chave: 'respostas', rotulo: 'Respostas', cor: 'var(--ad-serie-1)' },
];
</script>

<template>
    <CabecalhoPagina
        titulo="Respostas"
        subtitulo="Como os alunos estão usando as questões."
    >
        <SeletorPeriodo
            v-if="admin"
            :periodo="periodo"
            :base="admin.rotas.respostas"
        />
    </CabecalhoPagina>
    <article class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Respostas por dia</h2>
        <GraficoSeries
            titulo="Respostas por dia"
            :dados="serie"
            :series="serieResp"
            tipo="barra"
        />
    </article>
    <section class="ad-grade ad-grade--2">
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Matérias mais respondidas</h2>
            <EstadoVazio
                v-if="materias.length === 0"
                titulo="Ainda sem respostas"
            />
            <BarrasHorizontais
                v-else
                :itens="
                    materias.map((m) => ({
                        rotulo: m.nome,
                        valor: m.respostas,
                        texto: `${m.respostas} · ${m.pct_acerto === null ? '-' : `${m.pct_acerto}%`} acerto`,
                    }))
                "
            />
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Abandono de questionário</h2>
            <div class="ad-grade">
                <CartaoMetrica rotulo="Iniciados" :valor="abandono.iniciadas" />
                <CartaoMetrica
                    rotulo="Concluídos"
                    :valor="abandono.concluidas"
                />
                <CartaoMetrica
                    rotulo="Abandonados"
                    :valor="abandono.abandonadas"
                    :detalhe="
                        abandono.taxa_pct === null
                            ? 'sem dados'
                            : `${abandono.taxa_pct}% (sem finalizar há mais de 24 h)`
                    "
                />
            </div>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Questionários mais feitos</h2>
            <EstadoVazio
                v-if="questionarios.length === 0"
                titulo="Ainda sem questionários feitos"
            />
            <div v-else class="ad-rolagem">
                <table class="ad-tabela">
                    <thead>
                        <tr>
                            <th scope="col">Questionário</th>
                            <th scope="col" class="ad-dir">Tentativas</th>
                            <th scope="col" class="ad-dir">Concluídas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="q in questionarios" :key="q.id">
                            <td>
                                {{ q.titulo }}<br /><span class="ad-mudo">{{
                                    q.assunto
                                }}</span>
                            </td>
                            <td class="ad-dir">{{ q.tentativas }}</td>
                            <td class="ad-dir">{{ q.concluidas }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">
                Etiquetas de erro mais frequentes
            </h2>
            <EstadoVazio
                v-if="etiquetas_de_erro.length === 0"
                titulo="Ainda sem erros etiquetados"
            />
            <BarrasHorizontais
                v-else
                :itens="
                    etiquetas_de_erro.map((e) => ({
                        rotulo: `${e.nome} (${e.materia})`,
                        valor: e.total,
                    }))
                "
            />
        </article>
    </section>
</template>
