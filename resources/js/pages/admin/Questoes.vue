<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useId } from 'vue';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import DistribuicaoMini from '@/components/admin/DistribuicaoMini.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import Paginacao from '@/components/admin/Paginacao.vue';
import ResumoMaterias from '@/components/admin/ResumoMaterias.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import { useAdmin } from '@/composables/useAdmin';
import { useFiltros } from '@/composables/useFiltros';
import type { Paginado, QuestaoLinha, ResumoAssunto } from '@/types/admin';

const props = defineProps<{
    filtros: Record<
        'origem' | 'materia' | 'assunto' | 'status_juridico' | 'suspeita',
        string | null
    >;
    opcoes: {
        materias: { id: number; nome: string }[];
        assuntos: { id: number; materia_id: number; nome: string }[];
        status_juridico: string[];
    };
    resumo: {
        suspeitas: number;
        materias: (ResumoAssunto & { assuntos: ResumoAssunto[] })[];
    };
    questoes: Paginado<QuestaoLinha>;
}>();

const { filtros, aplicar, limpar } = useFiltros({ ...props.filtros });
const id = useId();
const { admin } = useAdmin();
const assuntos = computed(() =>
    props.opcoes.assuntos.filter(
        (a) =>
            !filtros.materia ||
            String(a.materia_id) === String(filtros.materia),
    ),
);
const rotuloMotivo: Record<string, string> = {
    acerto_baixo: 'acerto baixo',
    acerto_alto: 'acerto alto',
    distrator_domina: 'distrator domina',
    reports: 'reports',
};
const seg = (ms: number | null) =>
    ms === null ? '-' : `${(ms / 1000).toFixed(1)} s`;
</script>

<template>
    <CabecalhoPagina
        titulo="Questões"
        :subtitulo="`${resumo.suspeitas} questões suspeitas (acerto muito baixo ou alto, distrator dominante ou reports).`"
    >
        <a
            v-if="admin"
            :href="admin.exportar.questoes"
            download
            class="ad-btn ad-btn--claro"
            >Baixar CSV</a
        >
    </CabecalhoPagina>
    <form class="ad-cartao ad-filtros" @submit.prevent="aplicar()">
        <div class="ad-campo">
            <label :for="`${id}o`">Origem</label>
            <select
                :id="`${id}o`"
                v-model="filtros.origem"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Todas</option>
                <option value="autoral">Autoral</option>
                <option value="inep">INEP</option>
            </select>
        </div>
        <div class="ad-campo">
            <label :for="`${id}m`">Matéria</label>
            <select
                :id="`${id}m`"
                v-model="filtros.materia"
                class="ad-input"
                @change="
                    filtros.assunto = null;
                    aplicar();
                "
            >
                <option :value="null">Todas</option>
                <option
                    v-for="m in opcoes.materias"
                    :key="m.id"
                    :value="String(m.id)"
                >
                    {{ m.nome }}
                </option>
            </select>
        </div>
        <div class="ad-campo">
            <label :for="`${id}a`">Assunto</label>
            <select
                :id="`${id}a`"
                v-model="filtros.assunto"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Todos</option>
                <option v-for="a in assuntos" :key="a.id" :value="String(a.id)">
                    {{ a.nome }}
                </option>
            </select>
        </div>
        <div class="ad-campo">
            <label :for="`${id}s`">Status jurídico</label>
            <select
                :id="`${id}s`"
                v-model="filtros.status_juridico"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Todos</option>
                <option v-for="s in opcoes.status_juridico" :key="s" :value="s">
                    {{ s }}
                </option>
            </select>
        </div>
        <label class="ad-campo"
            ><span
                ><input
                    type="checkbox"
                    :checked="filtros.suspeita === '1'"
                    @change="
                        filtros.suspeita = ($event.target as HTMLInputElement)
                            .checked
                            ? '1'
                            : null;
                        aplicar();
                    "
                />
                Só suspeitas</span
            ></label
        >
        <button type="button" class="ad-btn ad-btn--claro" @click="limpar">
            Limpar
        </button>
    </form>

    <EstadoVazio
        v-if="questoes.dados.length === 0"
        titulo="Nenhuma questão com esses filtros"
    />
    <template v-else>
        <div class="ad-rolagem">
            <table class="ad-tabela">
                <caption class="ad-sr">
                    Lista de questões
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Origem</th>
                        <th scope="col">Matéria / assunto</th>
                        <th scope="col" class="ad-dir">Respostas</th>
                        <th scope="col" class="ad-dir">Acerto</th>
                        <th scope="col" class="ad-dir">Tempo</th>
                        <th scope="col">Distribuição</th>
                        <th scope="col">Sinais</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="q in questoes.dados" :key="q.id">
                        <td>
                            <Link :href="q.url" class="ad-link">{{
                                q.codigo
                            }}</Link>
                        </td>
                        <td>
                            {{ q.origem === 'inep' ? 'INEP' : 'Autoral' }}
                            <TagStatus :valor="q.status_juridico" />
                        </td>
                        <td>
                            {{ q.materia ?? '-' }}<br /><span class="ad-mudo">{{
                                q.assunto ?? ''
                            }}</span>
                        </td>
                        <td class="ad-dir">{{ q.respostas }}</td>
                        <td class="ad-dir">
                            {{
                                q.pct_acerto === null ? '-' : `${q.pct_acerto}%`
                            }}
                        </td>
                        <td class="ad-dir">{{ seg(q.tempo_medio_ms) }}</td>
                        <td><DistribuicaoMini :itens="q.distribuicao" /></td>
                        <td>
                            <span v-if="q.suspeita" class="ad-tag ad-tag--ruim"
                                >suspeita:
                                {{
                                    q.motivos_suspeita
                                        .map((m) => rotuloMotivo[m] ?? m)
                                        .join(', ')
                                }}</span
                            >
                            <span
                                v-if="q.reports_abertos"
                                class="ad-tag ad-tag--aviso"
                                >{{ q.reports_abertos }} reports</span
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Paginacao :paginado="questoes" />
    </template>
    <h2 class="ad-h2">Por matéria e assunto</h2>
    <ResumoMaterias :materias="resumo.materias" />
</template>
