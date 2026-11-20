<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmarAcao from '@/components/responsavel/ConfirmarAcao.vue';
import { rotas } from '@/lib/rotas';

const props = defineProps<{ token: string; mostrarEntrar: boolean }>();
const recusando = ref(false);
const form = useForm({});
const recusar = () => form.post(rotas.recusarConvite(props.token));
</script>

<template>
    <div class="gb-form">
        <div class="gb-acoes">
            <Link
                v-if="mostrarEntrar"
                :href="`${rotas.entrarResponsavel}?redirect=${encodeURIComponent(rotas.conviteResponsavel(token))}`"
                class="l-btn l-btn--orange"
            >
                Entrar e autorizar
            </Link>
            <button
                v-if="!recusando"
                type="button"
                class="l-btn l-btn--white"
                @click="recusando = true"
            >
                Recusar
            </button>
        </div>
        <ConfirmarAcao
            v-if="recusando"
            titulo="Recusar o convite?"
            texto="O aluno é avisado e continua sem acesso até outro responsável autorizar."
            rotulo-confirmar="Sim, recusar"
            :processando="form.processing"
            @confirmar="recusar"
            @cancelar="recusando = false"
        />
    </div>
</template>
