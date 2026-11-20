<script setup lang="ts">
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';

defineProps<{
    falhas: {
        total: number;
        ultimas: {
            id: number;
            fila: string;
            job: string | null;
            erro: string | null;
            quando: string;
        }[];
    };
    backup: {
        configurado: boolean;
        arquivo: string | null;
        quando: string | null;
        idade_horas: number | null;
    };
    scheduler: { ultimo_tick: string | null; atrasado: boolean };
    versao: string;
    inep: {
        exibicao_ligada: boolean;
        total: number;
        por_ano: Record<string, number>;
        por_status: Record<string, number>;
    };
}>();
</script>

<template>
    <CabecalhoPagina
        titulo="Saúde do sistema"
        subtitulo="O que precisa de olho técnico."
    />
    <section class="ad-grade ad-grade--2">
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Backup local</h2>
            <p v-if="!backup.configurado" class="ad-alerta">
                Diretório de backup não configurado.
            </p>
            <p v-else-if="!backup.quando" class="ad-alerta ad-alerta--ruim">
                Nenhum backup encontrado.
            </p>
            <dl v-else class="ad-dl">
                <div>
                    <dt>Último</dt>
                    <dd>{{ backup.quando }}</dd>
                </div>
                <div>
                    <dt>Idade</dt>
                    <dd :class="{ 'ad-erro': (backup.idade_horas ?? 0) > 36 }">
                        {{ backup.idade_horas }} h
                    </dd>
                </div>
                <div>
                    <dt>Arquivo</dt>
                    <dd>{{ backup.arquivo }}</dd>
                </div>
            </dl>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Scheduler</h2>
            <p v-if="!scheduler.ultimo_tick" class="ad-alerta ad-alerta--ruim">
                Nenhum tick registrado ainda.
            </p>
            <template v-else>
                <p
                    :class="
                        scheduler.atrasado
                            ? 'ad-alerta ad-alerta--ruim'
                            : 'ad-alerta ad-alerta--ok'
                    "
                >
                    {{ scheduler.atrasado ? 'Atrasado' : 'Rodando' }}. Último
                    tick: {{ scheduler.ultimo_tick }}
                </p>
            </template>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Deploy</h2>
            <p>
                Versão: <strong class="ad-num">{{ versao }}</strong>
            </p>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Questões do INEP</h2>
            <p>
                <span
                    class="ad-tag"
                    :class="
                        inep.exibicao_ligada ? 'ad-tag--ok' : 'ad-tag--aviso'
                    "
                    >exibição
                    {{ inep.exibicao_ligada ? 'ligada' : 'desligada' }}</span
                >
                · {{ inep.total }} questões
            </p>
            <dl class="ad-dl">
                <div v-for="(n, ano) in inep.por_ano" :key="ano">
                    <dt>{{ ano }}</dt>
                    <dd>{{ n }}</dd>
                </div>
                <div v-for="(n, s) in inep.por_status" :key="s">
                    <dt>{{ s }}</dt>
                    <dd>{{ n }}</dd>
                </div>
            </dl>
        </article>
    </section>
    <article class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">
            E-mails e jobs que falharam
            <span
                class="ad-tag"
                :class="falhas.total ? 'ad-tag--ruim' : 'ad-tag--ok'"
                >{{ falhas.total }}</span
            >
        </h2>
        <EstadoVazio
            v-if="falhas.ultimas.length === 0"
            titulo="Nenhuma falha na fila"
        />
        <div v-else class="ad-rolagem">
            <table class="ad-tabela">
                <thead>
                    <tr>
                        <th scope="col">Quando</th>
                        <th scope="col">Fila</th>
                        <th scope="col">Job</th>
                        <th scope="col">Erro</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="f in falhas.ultimas" :key="f.id">
                        <td class="ad-num">{{ f.quando }}</td>
                        <td>{{ f.fila }}</td>
                        <td>{{ f.job ?? '-' }}</td>
                        <td>{{ f.erro ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </article>
</template>
