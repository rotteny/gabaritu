<script setup lang="ts">
import {
    rotuloParentesco,
    TIPOS_CONVITE,
    formatarData,
} from '@/lib/responsavel';
import type { ConvitePendente, ResponsavelDoAluno } from '@/types/gabaritu';

defineProps<{
    responsaveis: ResponsavelDoAluno[];
    convites: ConvitePendente[];
}>();
</script>

<template>
    <div class="gb-bloco">
        <h3>Quem acompanha</h3>
        <ul class="gb-lista-linhas">
            <li v-for="r in responsaveis" :key="r.email_mascarado">
                <strong>{{ r.sou_eu ? 'Você' : r.nome }}</strong>
                ({{ rotuloParentesco(r.parentesco) }}) · {{ r.email_mascarado }}
            </li>
        </ul>
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
