<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AcoesConvite from '@/components/responsavel/AcoesConvite.vue';
import ConviteInvalido from '@/components/responsavel/ConviteInvalido.vue';
import ConviteResumo from '@/components/responsavel/ConviteResumo.vue';
import FormAutorizacao from '@/components/responsavel/FormAutorizacao.vue';
import type { ConviteDados } from '@/types/gabaritu';

const props = defineProps<{
    convite: ConviteDados | null;
    tem_conta?: boolean;
    logado_como_destinatario?: boolean;
    /** Se o backend não mandar, sai do último trecho da URL. */
    token?: string;
    /** Só vale quando `convite` é nulo. */
    vencido?: boolean;
}>();

const page = usePage();
const tokenAtual = computed(
    () => props.token ?? page.url.split('?')[0].split('/').pop() ?? '',
);
const semConta = computed(() => !props.tem_conta);
const mostrarForm = computed(
    () => semConta.value || props.logado_como_destinatario === true,
);
</script>

<template>
    <Head :title="convite ? 'Convite de responsável' : 'Convite inválido'" />
    <ConviteInvalido v-if="!convite" :vencido="vencido === true" />
    <template v-else>
        <ConviteResumo :convite="convite" />
        <FormAutorizacao
            v-if="mostrarForm"
            :token="tokenAtual"
            :convite="convite"
            :sem-conta="semConta"
        />
        <p v-else class="gb-aviso gb-aviso--info">
            Este e-mail já tem conta de responsável. Entre para autorizar.
        </p>
        <AcoesConvite :token="tokenAtual" :mostrar-entrar="!mostrarForm" />
    </template>
</template>
