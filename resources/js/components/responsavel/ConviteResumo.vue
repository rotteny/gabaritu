<script setup lang="ts">
import PeixotoSvg from '@/components/landing/PeixotoSvg.vue';
import {
    formatarData,
    rotuloParentesco,
    SERIES_ROTULO,
} from '@/lib/responsavel';
import type { ConviteDados } from '@/types/gabaritu';

defineProps<{ convite: ConviteDados }>();
</script>

<template>
    <section class="gb-cartao gb-prosa" aria-labelledby="convite-quem">
        <div class="gb-convite-peixe">
            <div class="gb-peixoto-mini" aria-hidden="true"><PeixotoSvg /></div>
            <h1 id="convite-quem" class="gb-subtitulo">
                <template v-if="convite.tipo === 'transferencia'">
                    Pedido para assumir a responsabilidade por
                    {{ convite.aluno.apelido }}
                </template>
                <template v-else-if="convite.tipo === 'autorizacao'">
                    {{ convite.aluno.apelido }} pediu a sua autorização
                </template>
                <template v-else>
                    Convite para acompanhar {{ convite.aluno.apelido }}
                </template>
            </h1>
        </div>
        <p class="gb-texto">
            <strong>{{ convite.aluno.apelido }}</strong>
            ({{ SERIES_ROTULO[convite.aluno.serie] }}) quer estudar para o ENEM
            no Gabaritu, uma plataforma gratuita de questões e revisão. Por lei,
            quem tem menos de 18 anos só usa depois que um responsável autoriza
            (ECA Digital, Lei 15.211/2025).
        </p>
        <p class="gb-texto">
            Parentesco informado:
            <strong>{{
                rotuloParentesco(convite.parentesco, convite.parentesco_outro)
            }}</strong
            >. Você pode confirmar ou corrigir ao autorizar.
        </p>
        <h2 class="gb-subtitulo">O que você vai poder fazer e ver</h2>
        <ul class="gb-convite-lista">
            <li>
                Ver as notas estimadas, os assuntos e o diagnóstico de erros do
                aluno.
            </li>
            <li>
                Baixar o teto de zoeira do Peixoto, o mascote que comenta erros
                e acertos.
            </li>
            <li>
                Convidar outro responsável, transferir a responsabilidade ou
                revogar a sua autorização.
            </li>
            <li>Apagar a conta do aluno e tudo o que ele fez.</li>
        </ul>
        <p class="gb-miudo">
            O convite vale até {{ formatarData(convite.expira_em) }}. O aluno é
            avisado da sua decisão.
        </p>
    </section>
</template>
