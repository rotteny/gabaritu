<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import RevelarEmail from '@/components/admin/RevelarEmail.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import { useAdmin } from '@/composables/useAdmin';
import type { ResponsavelLinha, Revelar } from '@/types/admin';

defineProps<{
    responsavel: ResponsavelLinha;
    alunos: {
        id: number;
        apelido: string | null;
        parentesco: string;
        status: string;
        url: string;
    }[];
    revelar: Revelar;
}>();
const { admin } = useAdmin();
</script>

<template>
    <CabecalhoPagina
        :titulo="responsavel.email_mascarado"
        subtitulo="Ficha do responsável. A abertura desta página foi registrada na auditoria."
    >
        <Link
            v-if="admin"
            :href="admin.rotas.responsaveis"
            class="ad-btn ad-btn--claro"
            >Voltar à lista</Link
        >
    </CabecalhoPagina>
    <section class="ad-cartao">
        <dl class="ad-dl">
            <div>
                <dt>Alunos vinculados</dt>
                <dd>{{ responsavel.alunos_vinculados }}</dd>
            </div>
            <div>
                <dt>Convites pendentes</dt>
                <dd>{{ responsavel.convites_pendentes }}</dd>
            </div>
            <div>
                <dt>Cadastro</dt>
                <dd>{{ responsavel.cadastro }}</dd>
            </div>
        </dl>
        <div class="ad-cartao-cab" />
        <RevelarEmail :revelar="revelar" />
    </section>
    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Alunos vinculados</h2>
        <EstadoVazio
            v-if="alunos.length === 0"
            titulo="Nenhum aluno vinculado"
        />
        <ul v-else class="ad-lista">
            <li v-for="a in alunos" :key="a.id">
                <Link :href="a.url" class="ad-link">{{
                    a.apelido ?? 'sem apelido'
                }}</Link>
                · {{ a.parentesco }} <TagStatus :valor="a.status" />
            </li>
        </ul>
    </section>
</template>
