<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConviteResponsavelAluno from '@/components/aluno/ConviteResponsavelAluno.vue';
import { rotas } from '@/lib/rotas';
import { formatarData, rotuloParentesco } from '@/lib/responsavel';
import type { ConviteDoAluno, ResponsavelDoAlunoConta } from '@/types/gabaritu';

defineProps<{
    responsaveis: ResponsavelDoAlunoConta[];
    convites: ConviteDoAluno[];
    podeConvidar: boolean;
}>();

const cancelar = (id: number) =>
    router.delete(rotas.cancelarConviteAluno(id), { preserveScroll: true });
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="acompanha-titulo">
        <h2 id="acompanha-titulo" class="gb-subtitulo">Quem acompanha você</h2>
        <p class="gb-texto">
            Os seus responsáveis veem as suas notas estimadas, os assuntos e o
            diagnóstico dos seus erros. Eles também podem baixar o nível de
            zoeira do Peixoto. É só isso que eles veem.
        </p>
        <ul class="gb-lista-linhas">
            <li v-for="r in responsaveis" :key="r.email_mascarado">
                <strong>{{ r.nome }}</strong> ({{
                    rotuloParentesco(r.parentesco)
                }}) · {{ r.email_mascarado }}
            </li>
        </ul>
        <p class="gb-miudo">
            Você não pode remover um responsável: só ele mesmo encerra o
            acompanhamento, pela conta dele.
        </p>
        <div v-if="convites.length > 0" class="gb-bloco">
            <h3>Convites pendentes</h3>
            <ul class="gb-lista-linhas">
                <li v-for="c in convites" :key="c.id">
                    {{ c.email_mascarado }} ({{
                        rotuloParentesco(c.parentesco)
                    }})
                    <span class="gb-miudo"
                        >vale até {{ formatarData(c.expira_em) }}</span
                    >
                    <button
                        type="button"
                        class="l-btn l-btn--white gb-btn-mini"
                        :aria-label="`Cancelar o convite para ${c.email_mascarado}`"
                        @click="cancelar(c.id)"
                    >
                        Cancelar
                    </button>
                </li>
            </ul>
        </div>
        <ConviteResponsavelAluno :pode-convidar="podeConvidar" />
    </section>
</template>
