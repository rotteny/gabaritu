<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import Paginacao from '@/components/admin/Paginacao.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import type { GrupoReport, Paginado, StatusReport } from '@/types/admin';

const props = defineProps<{
    filtros: { status: StatusReport };
    opcoes: { status: string[]; motivos: { valor: string; rotulo: string }[] };
    contagens: Record<StatusReport, number>;
    fila: Paginado<GrupoReport>;
}>();

const rotuloStatus: Record<StatusReport, string> = {
    aberto: 'Abertos',
    resolvido: 'Resolvidos',
    descartado: 'Descartados',
};
const motivo = (v: string) =>
    props.opcoes.motivos.find((m) => m.valor === v)?.rotulo ?? v;
const mudar = (url: string, status: string) =>
    router.patch(url, { status }, { preserveScroll: true });
</script>

<template>
    <CabecalhoPagina
        titulo="Reports"
        subtitulo="Erros apontados por alunos, agrupados por questão. Só o apelido aparece."
    >
        <nav class="ad-seg" aria-label="Status">
            <Link
                v-for="s in [
                    'aberto',
                    'resolvido',
                    'descartado',
                ] as StatusReport[]"
                :key="s"
                :href="`?status=${s}`"
                :aria-current="filtros.status === s ? 'true' : undefined"
            >
                {{ rotuloStatus[s] }} ({{ contagens[s] }})
            </Link>
        </nav>
    </CabecalhoPagina>
    <EstadoVazio v-if="fila.dados.length === 0" titulo="Nenhum report aqui"
        >Quando um aluno apontar erro numa questão, ele cai nesta
        fila.</EstadoVazio
    >
    <template v-else>
        <article v-for="g in fila.dados" :key="g.questao.id" class="ad-cartao">
            <div class="ad-cartao-cab">
                <h2 class="ad-h2">
                    <Link :href="g.questao.url" class="ad-link">{{
                        g.questao.codigo
                    }}</Link>
                    <span class="ad-mudo">
                        · {{ g.questao.materia ?? '-' }} /
                        {{ g.questao.assunto ?? '-' }} · {{ g.total }}
                        {{ g.total === 1 ? 'report' : 'reports' }}</span
                    >
                </h2>
                <span v-if="filtros.status === 'aberto'" class="ad-pag-botoes">
                    <button
                        type="button"
                        class="ad-btn ad-btn--mini"
                        @click="mudar(g.atualizar_todos, 'resolvido')"
                    >
                        Resolver todos
                    </button>
                    <button
                        type="button"
                        class="ad-btn ad-btn--claro ad-btn--mini"
                        @click="mudar(g.atualizar_todos, 'descartado')"
                    >
                        Descartar todos
                    </button>
                </span>
            </div>
            <p class="ad-lista">
                <span>
                    <template v-for="(n, chave) in g.por_motivo" :key="chave"
                        ><span v-if="n" class="ad-tag"
                            >{{ motivo(String(chave)) }}: {{ n }}</span
                        >
                    </template>
                </span>
            </p>
            <ul class="ad-lista">
                <li v-for="r in g.reports" :key="r.id">
                    <strong>{{ r.apelido }}</strong>
                    <span class="ad-mudo"
                        >· {{ motivo(r.motivo) }} · {{ r.criado_em }}</span
                    >
                    <TagStatus :valor="r.status" />
                    <p v-if="r.comentario" class="ad-texto-q">
                        {{ r.comentario }}
                    </p>
                    <span class="ad-pag-botoes">
                        <button
                            v-for="s in opcoes.status.filter(
                                (x) => x !== r.status,
                            )"
                            :key="s"
                            type="button"
                            class="ad-btn ad-btn--claro ad-btn--mini"
                            @click="mudar(r.url, s)"
                        >
                            Marcar como {{ s }}
                        </button>
                    </span>
                </li>
            </ul>
        </article>
        <Paginacao :paginado="fila" />
    </template>
</template>
