<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import DialogoModal from '@/components/DialogoModal.vue';
import { ErroHttp, postJson } from '@/lib/http';
import type { ConfigReport, MotivoReport } from '@/types/gabaritu';

const props = defineProps<{ report: ConfigReport }>();

const aberto = ref(false);
const motivo = ref<MotivoReport | null>(null);
const comentario = ref('');
const enviando = ref(false);
const enviado = ref(false);
const mensagem = ref<string | null>(null);
const idComentario = useId();

const restam = computed(
    () => props.report.max_comentario - comentario.value.length,
);

async function enviar() {
    if (!motivo.value || enviando.value) {
        return;
    }

    enviando.value = true;
    mensagem.value = null;

    try {
        await postJson(props.report.url, {
            motivo: motivo.value,
            ...(comentario.value.trim()
                ? { comentario: comentario.value.trim() }
                : {}),
        });
        enviado.value = true;
    } catch (e) {
        mensagem.value =
            e instanceof ErroHttp
                ? e.message
                : 'Não deu pra enviar agora. Tenta de novo.';

        if (e instanceof ErroHttp && e.status === 422) {
            enviado.value = true;
        }
    } finally {
        enviando.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="q-reportar"
        :disabled="enviado && !aberto"
        @click="aberto = true"
    >
        {{ enviado ? 'Reportado' : 'Reportar erro' }}
    </button>
    <DialogoModal v-model="aberto" titulo="Reportar erro" classe="q-report-dlg">
        <p v-if="enviado" class="gb-aviso gb-aviso--ok" role="status">
            {{ mensagem ?? 'Valeu! Vamos olhar essa questão.' }}
        </p>
        <form v-else class="q-report-form" @submit.prevent="enviar">
            <fieldset class="q-report-motivos">
                <legend>O que está errado?</legend>
                <label v-for="m in report.motivos" :key="m.valor">
                    <input
                        v-model="motivo"
                        type="radio"
                        name="motivo"
                        :value="m.valor"
                        required
                    />
                    {{ m.rotulo }}
                </label>
            </fieldset>
            <div>
                <label :for="idComentario">Quer explicar? (opcional)</label>
                <textarea
                    :id="idComentario"
                    v-model="comentario"
                    class="q-report-texto"
                    :maxlength="report.max_comentario"
                />
                <p class="q-report-dica">{{ restam }} caracteres</p>
            </div>
            <p v-if="mensagem" class="gb-aviso gb-aviso--erro" role="alert">
                {{ mensagem }}
            </p>
            <div class="q-report-acoes">
                <button
                    type="button"
                    class="l-btn l-btn--white"
                    @click="aberto = false"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="l-btn l-btn--orange"
                    :disabled="!motivo || enviando"
                >
                    Enviar
                </button>
            </div>
        </form>
        <div v-if="enviado" class="q-report-acoes">
            <button
                type="button"
                class="l-btn l-btn--orange"
                @click="aberto = false"
            >
                Fechar
            </button>
        </div>
    </DialogoModal>
</template>
