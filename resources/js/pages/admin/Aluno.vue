<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import RevelarEmail from '@/components/admin/RevelarEmail.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import { useAdmin } from '@/composables/useAdmin';
import type { AlunoLinha, Revelar } from '@/types/admin';

defineProps<{
    aluno: AlunoLinha;
    responsaveis: {
        id: number;
        email_mascarado: string;
        parentesco: string;
        parentesco_outro: string | null;
        status: string;
        aceito_em: string | null;
        url: string;
    }[];
    pedidos_e_convites: {
        id: number;
        tipo: string;
        email_mascarado: string;
        parentesco: string;
        expira_em: string | null;
    }[];
    desempenho: {
        materia: string;
        nota_media: number | null;
        assuntos_com_nota: number;
        respostas: number;
        pct_acerto: number | null;
    }[];
    revelar: Revelar;
}>();
const { admin } = useAdmin();
const pct = (v: number | null) =>
    v === null ? '-' : `${v.toLocaleString('pt-BR')}%`;
</script>

<template>
    <CabecalhoPagina
        :titulo="aluno.apelido"
        subtitulo="Ficha do aluno. A abertura desta página foi registrada na auditoria."
    >
        <Link
            v-if="admin"
            :href="admin.rotas.alunos"
            class="ad-btn ad-btn--claro"
            >Voltar à lista</Link
        >
    </CabecalhoPagina>

    <section class="ad-cartao">
        <dl class="ad-dl">
            <div>
                <dt>E-mail</dt>
                <dd>{{ aluno.email_mascarado }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><TagStatus :valor="aluno.status" /></dd>
            </div>
            <div>
                <dt>Série</dt>
                <dd>{{ aluno.serie_rotulo ?? '-' }}</dd>
            </div>
            <div>
                <dt>Idade</dt>
                <dd>{{ aluno.idade ?? '-' }}</dd>
            </div>
            <div>
                <dt>Cadastro</dt>
                <dd>{{ aluno.cadastro }}</dd>
            </div>
            <div>
                <dt>Última atividade</dt>
                <dd>{{ aluno.ultima_atividade ?? '-' }}</dd>
            </div>
            <div>
                <dt>Respostas</dt>
                <dd>{{ aluno.respostas }}</dd>
            </div>
        </dl>
        <div class="ad-cartao-cab" />
        <RevelarEmail :revelar="revelar" />
    </section>

    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Responsáveis vinculados</h2>
        <EstadoVazio
            v-if="responsaveis.length === 0"
            titulo="Nenhum responsável vinculado"
        />
        <ul v-else class="ad-lista">
            <li v-for="r in responsaveis" :key="r.id">
                <Link :href="r.url" class="ad-link">{{
                    r.email_mascarado
                }}</Link>
                ·
                {{
                    r.parentesco === 'outro' && r.parentesco_outro
                        ? r.parentesco_outro
                        : r.parentesco
                }}
                <TagStatus :valor="r.status" />
                <span class="ad-mudo"> aceito em {{ r.aceito_em ?? '-' }}</span>
            </li>
        </ul>
    </section>

    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Pedidos e convites pendentes</h2>
        <EstadoVazio
            v-if="pedidos_e_convites.length === 0"
            titulo="Nada pendente"
        />
        <ul v-else class="ad-lista">
            <li v-for="p in pedidos_e_convites" :key="p.id">
                {{ p.tipo }} · {{ p.email_mascarado }} · {{ p.parentesco }}
                <span class="ad-mudo"> expira em {{ p.expira_em ?? '-' }}</span>
            </li>
        </ul>
    </section>

    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Desempenho por matéria</h2>
        <EstadoVazio
            v-if="desempenho.length === 0"
            titulo="Ainda sem respostas"
        />
        <div v-else class="ad-rolagem">
            <table class="ad-tabela">
                <thead>
                    <tr>
                        <th scope="col">Matéria</th>
                        <th scope="col" class="ad-dir">Nota média</th>
                        <th scope="col" class="ad-dir">Assuntos com nota</th>
                        <th scope="col" class="ad-dir">Respostas</th>
                        <th scope="col" class="ad-dir">Acerto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in desempenho" :key="d.materia">
                        <td>{{ d.materia }}</td>
                        <td class="ad-dir">{{ d.nota_media ?? '-' }}</td>
                        <td class="ad-dir">{{ d.assuntos_com_nota }}</td>
                        <td class="ad-dir">{{ d.respostas }}</td>
                        <td class="ad-dir">{{ pct(d.pct_acerto) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
