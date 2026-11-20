<script setup lang="ts">
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import { useAdmin } from '@/composables/useAdmin';
import type { CelulaRetencao } from '@/types/admin';

defineProps<{
    marcos: number[];
    coortes: ({ semana: string; cadastros: number } & Record<
        string,
        unknown
    >)[];
}>();
const { admin } = useAdmin();

const celula = (c: Record<string, unknown>, n: number) =>
    c[`d${n}`] as CelulaRetencao | undefined;
/** Fundo em tom sólido (sem gradiente); o texto sempre mostra o número. */
function fundo(pct: number | null): string {
    if (pct === null) {
        return '#f1ecdf';
    }

    return pct >= 40
        ? '#cfe6bd'
        : pct >= 20
          ? '#e5efc9'
          : pct >= 10
            ? '#fff0c2'
            : '#fbe1dd';
}
</script>

<template>
    <CabecalhoPagina
        titulo="Retenção"
        subtitulo="Por semana de cadastro: % de alunos que responderam algo depois do dia do cadastro (janela acumulada)."
    >
        <a
            v-if="admin"
            :href="admin.exportar.retencao"
            download
            class="ad-btn ad-btn--claro"
            >Baixar CSV</a
        >
    </CabecalhoPagina>
    <EstadoVazio
        v-if="coortes.length === 0 || coortes.every((c) => c.cadastros === 0)"
        titulo="Ainda sem coortes"
        >As semanas aparecem aqui conforme os alunos se cadastram e
        voltam.</EstadoVazio
    >
    <div v-else class="ad-rolagem">
        <table class="ad-tabela">
            <caption class="ad-sr">
                Retenção por semana de cadastro
            </caption>
            <thead>
                <tr>
                    <th scope="col">Semana (segunda)</th>
                    <th scope="col" class="ad-dir">Cadastros</th>
                    <th
                        v-for="m in marcos"
                        :key="m"
                        scope="col"
                        class="ad-calor"
                    >
                        D{{ m }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="c in coortes" :key="c.semana">
                    <th scope="row" class="ad-num">{{ c.semana }}</th>
                    <td class="ad-dir">{{ c.cadastros }}</td>
                    <td
                        v-for="m in marcos"
                        :key="m"
                        class="ad-calor"
                        :style="{
                            background: fundo(celula(c, m)?.pct ?? null),
                        }"
                        :title="
                            celula(c, m)
                                ? `${celula(c, m)?.voltaram} de ${celula(c, m)?.base}`
                                : ''
                        "
                    >
                        {{
                            celula(c, m)?.pct === null ||
                            celula(c, m)?.pct === undefined
                                ? 'sem base'
                                : `${celula(c, m)?.pct}%`
                        }}
                        <span v-if="celula(c, m)" class="ad-sr">
                            ({{ celula(c, m)?.voltaram }} de
                            {{ celula(c, m)?.base }})</span
                        >
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
