<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { onBeforeUnmount, ref, useId, watch } from 'vue';
import DialogoModal from '@/components/DialogoModal.vue';
import type { Revelar } from '@/types/admin';

const props = defineProps<{ revelar: Revelar }>();

const aberto = ref(false);
const visivel = ref<string | null>(null);
const form = useForm({ motivo: '', codigo: '' });
const idMotivo = useId();
const idCodigo = useId();
let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => props.revelar.email,
    (email) => {
        clearTimeout(timer);
        visivel.value = email;

        if (email) {
            timer = setTimeout(() => (visivel.value = null), 30000);
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => clearTimeout(timer));

function enviar() {
    form.transform((d) =>
        props.revelar.exige_codigo ? d : { motivo: d.motivo },
    ).post(props.revelar.url, {
        preserveScroll: true,
        onSuccess: () => {
            aberto.value = false;
            form.reset();
        },
        onFinish: () => form.reset('codigo'),
    });
}
</script>

<template>
    <div class="ad-lista">
        <p v-if="visivel" class="ad-alerta ad-alerta--ok" role="status">
            E-mail: <strong>{{ visivel }}</strong>
            <span class="ad-dica">
                (some da tela em 30 segundos; o acesso foi registrado)</span
            >
        </p>
        <div>
            <button
                type="button"
                class="ad-btn ad-btn--claro"
                @click="aberto = true"
            >
                <Eye class="ad-ico" aria-hidden="true" /> Revelar e-mail
            </button>
        </div>
        <DialogoModal v-model="aberto" titulo="Revelar e-mail" classe="ad-dlg">
            <form class="ad-form" @submit.prevent="enviar">
                <p class="ad-dica">
                    O motivo fica registrado na auditoria, com o seu usuário e a
                    hora.
                </p>
                <div class="ad-campo">
                    <label :for="idMotivo">Motivo (5 a 200 caracteres)</label>
                    <textarea
                        :id="idMotivo"
                        v-model="form.motivo"
                        class="ad-input"
                        minlength="5"
                        maxlength="200"
                        required
                    />
                    <p v-if="form.errors.motivo" class="ad-erro" role="alert">
                        {{ form.errors.motivo }}
                    </p>
                </div>
                <div v-if="revelar.exige_codigo" class="ad-campo">
                    <label :for="idCodigo">Código do app autenticador</label>
                    <input
                        :id="idCodigo"
                        v-model="form.codigo"
                        class="ad-input ad-num"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        required
                    />
                    <p v-if="form.errors.codigo" class="ad-erro" role="alert">
                        {{ form.errors.codigo }}
                    </p>
                </div>
                <div class="ad-dlg-acoes">
                    <button
                        type="button"
                        class="ad-btn ad-btn--claro"
                        @click="aberto = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="ad-btn"
                        :disabled="form.processing"
                    >
                        Revelar
                    </button>
                </div>
            </form>
        </DialogoModal>
    </div>
</template>
