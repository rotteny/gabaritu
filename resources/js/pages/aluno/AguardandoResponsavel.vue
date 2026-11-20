<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import CorrigirEmailResponsavel from '@/components/responsavel/CorrigirEmailResponsavel.vue';
import ReenviarConvite from '@/components/responsavel/ReenviarConvite.vue';
import BotaoSair from '@/components/aluno/BotaoSair.vue';
import { formatarData } from '@/lib/responsavel';
import type { Fala } from '@/types/gabaritu';

defineProps<{
    email_responsavel_mascarado: string;
    convite_expira_em: string | null;
    recusado: boolean;
    reenvios_restantes: number;
    fala: Fala;
}>();
</script>

<template>
    <Head title="Aguardando o seu responsável" />
    <h1 class="gb-titulo">
        {{
            recusado
                ? 'Seu responsável recusou'
                : 'Falta o seu responsável autorizar'
        }}
    </h1>
    <FalaPeixoto :fala="fala" />
    <section
        class="gb-cartao gb-form gb-prosa"
        aria-labelledby="aguarda-por-que"
    >
        <h2 id="aguarda-por-que" class="gb-subtitulo">Por que isso?</h2>
        <p class="gb-texto">
            A lei que protege crianças e adolescentes na internet (o ECA
            Digital, Lei 15.211/2025) exige que a conta de quem é menor ligue a
            um responsável. No Gabaritu vale para todo mundo com menos de 18
            anos. Assim que ele autorizar, o seu acesso é liberado.
        </p>
        <p v-if="recusado" class="gb-aviso gb-aviso--erro" role="alert">
            Seu responsável recusou a autorização. Converse com ele e, se foi
            engano ou o e-mail estava errado, corrija o e-mail abaixo.
        </p>
        <p v-else class="gb-aviso gb-aviso--info" role="status">
            Enviamos um convite para
            <strong>{{ email_responsavel_mascarado }}</strong
            ><template v-if="convite_expira_em">
                . Ele vale até
                <strong>{{ formatarData(convite_expira_em) }}</strong></template
            >.
        </p>
        <p v-if="recusado" class="gb-miudo">
            O convite era para {{ email_responsavel_mascarado }}.
        </p>
        <ReenviarConvite v-if="!recusado" :restantes="reenvios_restantes" />
        <CorrigirEmailResponsavel />
        <p class="gb-miudo">
            Sem autorização em 30 dias, a conta é apagada. Você também pode
            apagar a conta antes, na página Conta.
        </p>
        <div class="gb-acoes"><BotaoSair /></div>
    </section>
</template>
