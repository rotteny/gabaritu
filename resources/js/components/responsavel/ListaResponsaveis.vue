<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import VinculoSuspenso from '@/components/responsavel/VinculoSuspenso.vue';
import { formatarData, nomeDoVinculo, TIPOS_CONVITE } from '@/lib/responsavel';
import { rotas } from '@/lib/rotas';
import type { ConvitePendente, ResponsavelDoAluno } from '@/types/gabaritu';

const props = defineProps<{
    alunoId: number;
    apelido: string;
    responsaveis: ResponsavelDoAluno[];
    convites: ConvitePendente[];
    podeRestaurar?: boolean;
}>();

const cancelar = (convite: ConvitePendente) =>
    router.delete(
        rotas.cancelarConviteDoAlunoPeloResponsavel(
            props.alunoId,
            convite.id as number,
        ),
        { preserveScroll: true },
    );

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
                :pode-restaurar="podeRestaurar !== false"
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
                    <button
                        v-if="c.pode_cancelar && c.id"
                        type="button"
                        class="l-btn l-btn--white gb-btn-mini"
                        @click="cancelar(c)"
                    >
                        Cancelar convite
                    </button>
                </li>
            </ul>
        </template>
    </div>
</template>
