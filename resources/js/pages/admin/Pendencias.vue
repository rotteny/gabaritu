<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';

defineProps<{
    autorizacoes_paradas: {
        dias: number;
        total: number;
        itens: {
            id: number;
            apelido: string;
            parado_ha_dias: number;
            email_responsavel_mascarado: string | null;
            url: string;
        }[];
    };
    convites_expirados: number;
    contestacoes: {
        total: number;
        itens: {
            aluno_id: number;
            aluno: string | null;
            parentesco: string;
            desde: string | null;
            aluno_url: string;
            responsavel_url: string;
        }[];
    };
    pedidos_de_responsavel: {
        total: number;
        itens: {
            id: number;
            tipo: string;
            email_mascarado: string;
            parentesco: string;
            criado_em: string | null;
            expira_em: string | null;
        }[];
    };
    contas_apagadas_por_mes: {
        mes: string;
        alunos: number;
        responsaveis: number;
    }[];
}>();
</script>

<template>
    <CabecalhoPagina
        titulo="Pendências legais"
        subtitulo="O que depende de um responsável ou pede atenção. Listas mostram até 50 itens."
    />
    <section class="ad-grade ad-grade--2">
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">
                Autorizações paradas há mais de
                {{ autorizacoes_paradas.dias }} dias
                <span class="ad-tag ad-tag--aviso">{{
                    autorizacoes_paradas.total
                }}</span>
            </h2>
            <EstadoVazio
                v-if="autorizacoes_paradas.itens.length === 0"
                titulo="Nenhuma parada"
            />
            <ul v-else class="ad-lista">
                <li v-for="i in autorizacoes_paradas.itens" :key="i.id">
                    <Link :href="i.url" class="ad-link">{{ i.apelido }}</Link> ·
                    há {{ i.parado_ha_dias }} dias
                    <span class="ad-mudo">
                        ·
                        {{
                            i.email_responsavel_mascarado ??
                            'sem e-mail de responsável'
                        }}</span
                    >
                </li>
            </ul>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">Convites expirados</h2>
            <p class="ad-metrica">
                <strong class="ad-num">{{ convites_expirados }}</strong>
            </p>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">
                Contestações de vínculo
                <span class="ad-tag ad-tag--ruim">{{
                    contestacoes.total
                }}</span>
            </h2>
            <EstadoVazio
                v-if="contestacoes.itens.length === 0"
                titulo="Nenhuma contestação"
            />
            <ul v-else class="ad-lista">
                <li
                    v-for="c in contestacoes.itens"
                    :key="`${c.aluno_id}-${c.responsavel_url}`"
                >
                    <Link :href="c.aluno_url" class="ad-link">{{
                        c.aluno ?? 'aluno'
                    }}</Link>
                    contesta
                    <Link :href="c.responsavel_url" class="ad-link"
                        >o responsável</Link
                    >
                    ({{ c.parentesco }})
                    <span class="ad-mudo"> desde {{ c.desde ?? '-' }}</span>
                </li>
            </ul>
        </article>
        <article class="ad-cartao">
            <h2 class="ad-h2 ad-cartao-cab">
                Pedidos iniciados por responsável
                <span class="ad-tag">{{ pedidos_de_responsavel.total }}</span>
            </h2>
            <EstadoVazio
                v-if="pedidos_de_responsavel.itens.length === 0"
                titulo="Nenhum pedido pendente"
            />
            <ul v-else class="ad-lista">
                <li v-for="p in pedidos_de_responsavel.itens" :key="p.id">
                    {{ p.tipo }} · {{ p.email_mascarado }} · {{ p.parentesco }}
                    <span class="ad-mudo">
                        criado {{ p.criado_em ?? '-' }}, expira
                        {{ p.expira_em ?? '-' }}</span
                    >
                </li>
            </ul>
        </article>
    </section>
    <article class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Contas apagadas por mês</h2>
        <EstadoVazio
            v-if="contas_apagadas_por_mes.length === 0"
            titulo="Nenhuma conta apagada registrada"
            >A contagem começa a valer a partir do deploy desta
            função.</EstadoVazio
        >
        <div v-else class="ad-rolagem">
            <table class="ad-tabela">
                <thead>
                    <tr>
                        <th scope="col">Mês</th>
                        <th scope="col" class="ad-dir">Alunos</th>
                        <th scope="col" class="ad-dir">Responsáveis</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in contas_apagadas_por_mes" :key="m.mes">
                        <td class="ad-num">{{ m.mes }}</td>
                        <td class="ad-dir">{{ m.alunos }}</td>
                        <td class="ad-dir">{{ m.responsaveis }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </article>
</template>
