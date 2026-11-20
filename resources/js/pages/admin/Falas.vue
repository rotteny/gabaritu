<script setup lang="ts">
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import type { FalaRank, GrupoFala } from '@/types/admin';

defineProps<{
    tem_feedback: boolean;
    mais_curtidas: FalaRank[];
    mais_rejeitadas: FalaRank[];
    por_nivel: GrupoFala[];
    por_chave: GrupoFala[];
    mais_exibidas: {
        id: number;
        texto: string;
        nivel: string;
        chave: string;
        exibicoes: number;
    }[];
}>();
</script>

<template>
    <CabecalhoPagina
        titulo="Falas do Peixoto"
        subtitulo="Como as falas estão sendo recebidas. Aqui o painel só lê o catálogo."
    />
    <template v-if="tem_feedback">
        <section class="ad-grade ad-grade--2">
            <article
                v-for="bloco in [
                    { t: 'Mais curtidas', l: mais_curtidas },
                    { t: 'Mais rejeitadas', l: mais_rejeitadas },
                ]"
                :key="bloco.t"
                class="ad-cartao"
            >
                <h2 class="ad-h2 ad-cartao-cab">{{ bloco.t }}</h2>
                <EstadoVazio
                    v-if="bloco.l.length === 0"
                    titulo="Sem votos ainda"
                />
                <ul v-else class="ad-lista">
                    <li v-for="f in bloco.l" :key="f.id">
                        {{ f.texto }}
                        <span class="ad-mudo">
                            · nível {{ f.nivel }} · {{ f.chave }} ·
                            {{ f.votos }} votos</span
                        >
                    </li>
                </ul>
            </article>
        </section>
        <section class="ad-grade ad-grade--2">
            <article
                v-for="bloco in [
                    { t: 'Por nível de zoeira', l: por_nivel },
                    { t: 'Por chave (top 10)', l: por_chave },
                ]"
                :key="bloco.t"
                class="ad-cartao"
            >
                <h2 class="ad-h2 ad-cartao-cab">{{ bloco.t }}</h2>
                <div class="ad-rolagem">
                    <table class="ad-tabela">
                        <thead>
                            <tr>
                                <th scope="col">Grupo</th>
                                <th scope="col" class="ad-dir">Curtidas</th>
                                <th scope="col" class="ad-dir">Rejeições</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="g in bloco.l" :key="g.grupo">
                                <td>{{ g.grupo }}</td>
                                <td class="ad-dir">{{ g.curtidas }}</td>
                                <td class="ad-dir">{{ g.rejeicoes }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </template>
    <article v-else class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">
            Mais exibidas <span class="ad-tag ad-tag--aviso">sem feedback</span>
        </h2>
        <p class="ad-dica">
            Ainda não há curtida nem rejeição registrada. Mostrando as falas
            mais exibidas.
        </p>
        <EstadoVazio
            v-if="mais_exibidas.length === 0"
            titulo="Nenhuma fala exibida ainda"
        />
        <ul v-else class="ad-lista">
            <li v-for="f in mais_exibidas" :key="f.id">
                {{ f.texto }}
                <span class="ad-mudo">
                    · nível {{ f.nivel }} · {{ f.chave }} ·
                    {{ f.exibicoes }} exibições</span
                >
            </li>
        </ul>
    </article>
</template>
