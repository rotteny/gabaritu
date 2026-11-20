<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, useId } from 'vue';

type Acoes = {
    confirmar: string;
    desafiar: string;
    continuar: string;
    sair: string;
};

const props = defineProps<{
    modo: 'configurar' | 'desafiar' | 'codigos';
    acoes: Acoes;
    segredo?: string;
    qr_svg?: string;
    otpauth_url?: string;
    codigos?: string[];
}>();

const form = useForm({ codigo: '', recuperacao: '' });
const usarRecuperacao = ref(false);
const idCodigo = useId();
const idRec = useId();
const guardou = ref(false);
const copiado = ref(false);

const titulo = computed(
    () =>
        ({
            configurar: 'Configurar verificação em duas etapas',
            desafiar: 'Verificação em duas etapas',
            codigos: 'Guarde seus códigos de recuperação',
        })[props.modo],
);

function confirmar() {
    form.transform((d) => ({ codigo: d.codigo })).post(props.acoes.confirmar, {
        onFinish: () => form.reset('codigo'),
    });
}

function desafiar() {
    form.transform((d) =>
        usarRecuperacao.value
            ? { recuperacao: d.recuperacao }
            : { codigo: d.codigo },
    ).post(props.acoes.desafiar, { onFinish: () => form.reset() });
}

async function copiar() {
    await navigator.clipboard?.writeText((props.codigos ?? []).join('\n'));
    copiado.value = true;
}
</script>

<template>
    <Head :title="`${titulo} · Admin`" />
    <div class="ad-cartao ad-form">
        <h1 class="ad-h2">{{ titulo }}</h1>

        <template v-if="modo === 'configurar'">
            <p>
                Leia o QR com um app autenticador e digite o código de 6 dígitos
                que ele mostrar.
            </p>
            <div
                class="ad-qr"
                role="img"
                aria-label="QR code para o app autenticador"
                v-html="qr_svg"
            />
            <p class="ad-dica">
                Se não conseguir ler o QR, digite esta chave no app:
            </p>
            <p class="ad-segredo">{{ segredo }}</p>
            <form class="ad-form" @submit.prevent="confirmar">
                <div class="ad-campo">
                    <label :for="idCodigo">Código de 6 dígitos</label>
                    <input
                        :id="idCodigo"
                        v-model="form.codigo"
                        class="ad-input ad-num"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                        autofocus
                    />
                    <p v-if="form.errors.codigo" class="ad-erro" role="alert">
                        {{ form.errors.codigo }}
                    </p>
                </div>
                <button
                    type="submit"
                    class="ad-btn"
                    :disabled="form.processing"
                >
                    Confirmar
                </button>
            </form>
        </template>

        <template v-else-if="modo === 'codigos'">
            <p class="ad-alerta" role="note">
                Estes 8 códigos aparecem só agora. Cada um vale uma vez e serve
                se você perder o app autenticador. Guarde num lugar seguro.
            </p>
            <ul class="ad-codigos">
                <li v-for="c in codigos" :key="c">{{ c }}</li>
            </ul>
            <button type="button" class="ad-btn ad-btn--claro" @click="copiar">
                {{ copiado ? 'Copiado' : 'Copiar códigos' }}
            </button>
            <label class="ad-campo">
                <span
                    ><input v-model="guardou" type="checkbox" /> Guardei os
                    códigos num lugar seguro</span
                >
            </label>
            <button
                type="button"
                class="ad-btn"
                :disabled="!guardou"
                @click="router.visit(acoes.continuar)"
            >
                Continuar para o painel
            </button>
        </template>

        <form v-else class="ad-form" @submit.prevent="desafiar">
            <div v-if="!usarRecuperacao" class="ad-campo">
                <label :for="idCodigo">Código do app autenticador</label>
                <input
                    :id="idCodigo"
                    v-model="form.codigo"
                    class="ad-input ad-num"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autofocus
                />
                <p v-if="form.errors.codigo" class="ad-erro" role="alert">
                    {{ form.errors.codigo }}
                </p>
            </div>
            <div v-else class="ad-campo">
                <label :for="idRec">Código de recuperação</label>
                <input
                    :id="idRec"
                    v-model="form.recuperacao"
                    class="ad-input"
                    placeholder="xxxxxx-xxxxxx"
                    autocomplete="off"
                    required
                    autofocus
                />
                <p v-if="form.errors.recuperacao" class="ad-erro" role="alert">
                    {{ form.errors.recuperacao }}
                </p>
            </div>
            <button type="submit" class="ad-btn" :disabled="form.processing">
                Verificar
            </button>
            <button
                type="button"
                class="ad-btn ad-btn--claro"
                @click="
                    usarRecuperacao = !usarRecuperacao;
                    form.clearErrors();
                "
            >
                {{
                    usarRecuperacao
                        ? 'Usar o app autenticador'
                        : 'Usar código de recuperação'
                }}
            </button>
        </form>

        <button
            v-if="modo !== 'codigos'"
            type="button"
            class="ad-btn ad-btn--claro ad-btn--mini"
            @click="router.post(acoes.sair)"
        >
            Sair
        </button>
    </div>
</template>
