<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConviteResponsavelAluno from '@/components/aluno/ConviteResponsavelAluno.vue';
import ContestarResponsavel from '@/components/aluno/ContestarResponsavel.vue';
import PedidosVinculoAluno from '@/components/aluno/PedidosVinculoAluno.vue';
import { rotas } from '@/lib/rotas';
import { formatarData, nomeDoVinculo } from '@/lib/responsavel';
import type {
    ConviteDoAluno,
    PedidoDeVinculo,
    ResponsavelDoAlunoConta,
} from '@/types/gabaritu';

defineProps<{
    responsaveis: ResponsavelDoAlunoConta[];
    convites: ConviteDoAluno[];
    pedidos: PedidoDeVinculo[];
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
            <li v-for="r in responsaveis" :key="r.id">
                <strong>{{ r.nome }}</strong> ({{ nomeDoVinculo(r) }}) ·
                {{ r.email_mascarado }}
                <span class="gb-miudo"
                    >desde {{ formatarData(r.autorizado_em) }}</span
                >
                <span
                    v-if="r.status === 'suspenso'"
                    class="gb-tag gb-tag--cinza"
                    >acesso suspenso</span
                >
                <ContestarResponsavel v-else :id="r.id" :nome="r.nome" />
            </li>
        </ul>
        <p class="gb-miudo">
            Você não remove um responsável sozinho: só ele encerra o
            acompanhamento. Se alguém aí não é seu responsável de verdade, use
            "Essa pessoa não é meu responsável" e o acesso dela é suspenso na
            hora.
        </p>
        <PedidosVinculoAluno :pedidos="pedidos" />
        <div v-if="convites.length > 0" class="gb-bloco">
            <h3>Convites pendentes</h3>
            <ul class="gb-lista-linhas">
                <li v-for="c in convites" :key="c.id">
                    {{ c.email_mascarado }} ({{ nomeDoVinculo(c) }})
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
