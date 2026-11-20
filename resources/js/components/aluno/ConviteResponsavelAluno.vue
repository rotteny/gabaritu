<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Aviso from '@/components/aluno/Aviso.vue';
import Campo from '@/components/aluno/Campo.vue';
import CampoParentesco from '@/components/responsavel/CampoParentesco.vue';
import { rotas } from '@/lib/rotas';

defineProps<{ podeConvidar: boolean }>();

const form = useForm({ email: '', parentesco: 'mae', parentesco_outro: '' });
const enviar = () =>
    form.post(rotas.contaResponsaveis, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <div class="gb-bloco">
        <h3>Convidar outro responsável</h3>
        <Aviso v-if="!podeConvidar" tipo="info">
            Você chegou ao limite: no máximo 5 responsáveis, e 3 convites por
            dia. Tente de novo amanhã.
        </Aviso>
        <form v-else class="gb-form" novalidate @submit.prevent="enviar">
            <Campo
                v-model="form.email"
                rotulo="E-mail do responsável"
                tipo="email"
                autocomplete="off"
                :erro="form.errors.email"
                obrigatorio
            />
            <CampoParentesco
                v-model:parentesco="form.parentesco"
                v-model:outro="form.parentesco_outro"
                rotulo="Parentesco com você"
                :erro-parentesco="form.errors.parentesco"
                :erro-outro="form.errors.parentesco_outro"
            />
            <p
                v-if="form.recentlySuccessful"
                class="gb-aviso gb-aviso--ok"
                role="status"
            >
                Convite enviado.
            </p>
            <div class="gb-acoes">
                <button
                    type="submit"
                    class="l-btn l-btn--orange gb-btn-mini"
                    :disabled="form.processing"
                >
                    Enviar convite
                </button>
            </div>
        </form>
    </div>
</template>
