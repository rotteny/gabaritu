<script setup lang="ts">
import VinculoSuspenso from '@/components/responsavel/VinculoSuspenso.vue';
import { formatarData, nomeDoVinculo, TIPOS_CONVITE } from '@/lib/responsavel';
import type { ConvitePendente, ResponsavelDoAluno } from '@/types/gabaritu';

defineProps<{
    alunoId: number;
    apelido: string;
    responsaveis: ResponsavelDoAluno[];
    convites: ConvitePendente[];
}>();

const comId = (r: ResponsavelDoAluno) =>
    r as ResponsavelDoAluno & { id: number };
</script>

<template>
    <div class="gb-bloco">
        <h3>Quem acompanha</h3>
        <ul class="gb-lista-linhas">
            <template v-for="r in responsaveis" :key="r.email_mascarado">
                <li v-if="r.status === 'ativo'">
                    <strong>{{ r.sou_eu ? 'Você' : r.nome }}</strong>
                    ({{ nomeDoVinculo(r) }}) · {{ r.email_mascarado }}
                    <span class="gb-miudo">
                        autorizou em {{ formatarData(r.autorizado_em) }}</span
                    >
                </li>
            </template>
        </ul>
        <template v-for="r in responsaveis" :key="`s-${r.email_mascarado}`">
            <VinculoSuspenso
                v-if="r.status === 'suspenso' && !r.sou_eu && r.id"
                :aluno-id="alunoId"
                :apelido="apelido"
                :responsavel="comId(r)"
            />
        </template>
        <template v-if="convites.length > 0">
            <h3>Convites pendentes</h3>
            <ul class="gb-lista-linhas">
                <li v-for="c in convites" :key="c.email_mascarado + c.tipo">
                    {{ c.email_mascarado }}
                    <span class="gb-tag gb-tag--cinza">{{
                        TIPOS_CONVITE[c.tipo]
                    }}</span>
                    <span class="gb-miudo">
                        vale até {{ formatarData(c.expira_em) }}</span
                    >
                </li>
            </ul>
        </template>
    </div>
</template>
