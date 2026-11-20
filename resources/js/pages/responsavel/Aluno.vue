<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AvatarAluno from '@/components/aluno/AvatarAluno.vue';
import DesempenhoLista from '@/components/aluno/DesempenhoLista.vue';
import StatusAlunoTag from '@/components/responsavel/StatusAlunoTag.vue';
import { rotas } from '@/lib/rotas';
import { SERIES_ROTULO } from '@/lib/responsavel';
import type { AlunoAcompanhado, DesempenhoMateria } from '@/types/gabaritu';

defineProps<{ aluno: AlunoAcompanhado; materias: DesempenhoMateria[] }>();
</script>

<template>
    <Head :title="`Acompanhando ${aluno.apelido}`" />
    <Link :href="rotas.painel" class="gb-link gb-voltar-link">
        <ArrowLeft :size="18" aria-hidden="true" /> Meus alunos
    </Link>
    <header class="gb-aluno-topo">
        <AvatarAluno :slug="aluno.avatar" :tamanho="56" />
        <h1 class="gb-titulo">Acompanhando: {{ aluno.apelido }}</h1>
        <StatusAlunoTag :status="aluno.status" />
    </header>
    <p class="gb-aluno-meta">
        {{ SERIES_ROTULO[aluno.serie] }} · {{ aluno.idade }} anos
    </p>
    <p class="gb-aviso gb-aviso--info">
        Você está só olhando: não dá para alterar nada por aqui. As notas são
        estimativas do Gabaritu, não a nota oficial do ENEM.
    </p>
    <DesempenhoLista
        :materias="materias"
        :vazio="`${aluno.apelido} ainda não respondeu nenhuma questão.`"
    />
</template>
