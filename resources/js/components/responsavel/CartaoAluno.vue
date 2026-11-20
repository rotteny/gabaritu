<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AvatarAluno from '@/components/aluno/AvatarAluno.vue';
import AcoesAluno from '@/components/responsavel/AcoesAluno.vue';
import ListaResponsaveis from '@/components/responsavel/ListaResponsaveis.vue';
import NotasResumo from '@/components/responsavel/NotasResumo.vue';
import StatusAlunoTag from '@/components/responsavel/StatusAlunoTag.vue';
import TetoZoeira from '@/components/responsavel/TetoZoeira.vue';
import { rotas } from '@/lib/rotas';
import { SERIES_ROTULO } from '@/lib/responsavel';
import type { AlunoAcompanhado } from '@/types/gabaritu';

defineProps<{ aluno: AlunoAcompanhado }>();
</script>

<template>
    <article class="gb-cartao gb-form" :aria-label="`Aluno ${aluno.apelido}`">
        <header class="gb-aluno-topo">
            <AvatarAluno :slug="aluno.avatar" :tamanho="48" />
            <h2 class="gb-subtitulo">{{ aluno.apelido }}</h2>
            <StatusAlunoTag :status="aluno.status" />
        </header>
        <p class="gb-aluno-meta">
            {{ SERIES_ROTULO[aluno.serie] }} · {{ aluno.idade }} anos
        </p>
        <NotasResumo
            v-if="aluno.notas && aluno.notas.length > 0"
            :notas="aluno.notas"
        />
        <div class="gb-acoes">
            <Link
                :href="rotas.alunoAcompanhado(aluno.id)"
                class="l-btn l-btn--orange"
            >
                Ver desempenho
            </Link>
        </div>
        <TetoZoeira
            :aluno-id="aluno.id"
            :teto="aluno.teto_zoeira_responsavel"
        />
        <ListaResponsaveis
            :responsaveis="aluno.responsaveis"
            :convites="aluno.convites_pendentes"
        />
        <AcoesAluno :aluno="aluno" />
    </article>
</template>
