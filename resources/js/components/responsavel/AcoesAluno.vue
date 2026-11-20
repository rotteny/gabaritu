<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmarAcao from '@/components/responsavel/ConfirmarAcao.vue';
import FormConviteResponsavel from '@/components/responsavel/FormConviteResponsavel.vue';
import { rotas } from '@/lib/rotas';
import type { AlunoAcompanhado } from '@/types/gabaritu';

const props = defineProps<{ aluno: AlunoAcompanhado }>();

type Acao = 'convidar' | 'transferir' | 'revogar' | 'apagar';
const aberta = ref<Acao | null>(null);
const unico = computed(() => props.aluno.responsaveis.length <= 1);

const formVinculo = useForm({});
const formApagar = useForm({ senha: '' });
const primeiroErro = computed(
    () => Object.values(formVinculo.errors)[0] as string | undefined,
);

const revogar = () =>
    formVinculo.delete(rotas.vinculoAluno(props.aluno.id), {
        preserveScroll: true,
        onSuccess: () => (aberta.value = null),
    });
const apagar = (senha: string) => {
    formApagar.senha = senha;
    formApagar.delete(rotas.alunoAcompanhado(props.aluno.id), {
        preserveScroll: true,
        onError: () => formApagar.reset('senha'),
    });
};
const alternar = (acao: Acao) =>
    (aberta.value = aberta.value === acao ? null : acao);
</script>

<template>
    <div class="gb-bloco">
        <h3>Ações</h3>
        <div class="gb-acoes-aluno">
            <button
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                :aria-expanded="aberta === 'convidar'"
                @click="alternar('convidar')"
            >
                Convidar co-responsável
            </button>
            <button
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                :aria-expanded="aberta === 'transferir'"
                @click="alternar('transferir')"
            >
                Transferir
            </button>
            <button
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                :aria-expanded="aberta === 'revogar'"
                :disabled="unico"
                @click="alternar('revogar')"
            >
                Revogar
            </button>
            <button
                type="button"
                class="l-btn l-btn--perigo gb-btn-mini"
                :aria-expanded="aberta === 'apagar'"
                @click="alternar('apagar')"
            >
                Apagar a conta do aluno
            </button>
        </div>
        <p v-if="unico" class="gb-miudo">
            Você é o único responsável, então não pode revogar. Transfira para
            outra pessoa ou apague a conta do aluno.
        </p>
        <FormConviteResponsavel
            v-if="aberta === 'convidar'"
            :aluno-id="aluno.id"
            tipo="coresponsavel"
            @fechar="aberta = null"
        />
        <FormConviteResponsavel
            v-if="aberta === 'transferir'"
            :aluno-id="aluno.id"
            tipo="transferencia"
            @fechar="aberta = null"
        />
        <ConfirmarAcao
            v-if="aberta === 'revogar'"
            titulo="Revogar a sua autorização?"
            :texto="`Você deixa de acompanhar ${aluno.apelido}. Se não sobrar nenhum responsável, a conta do aluno volta a aguardar autorização.`"
            rotulo-confirmar="Sim, revogar"
            :processando="formVinculo.processing"
            @confirmar="revogar"
            @cancelar="aberta = null"
        />
        <p
            v-if="aberta === 'revogar' && primeiroErro"
            class="gb-erro"
            role="alert"
        >
            {{ primeiroErro }}
        </p>
        <ConfirmarAcao
            v-if="aberta === 'apagar'"
            titulo="Apagar a conta do aluno?"
            :texto="`Isso apaga a conta de ${aluno.apelido} e tudo que ele fez: respostas, notas e histórico. Não dá pra desfazer.`"
            rotulo-confirmar="Apagar tudo agora"
            pede-senha
            :erro-senha="formApagar.errors.senha"
            :processando="formApagar.processing"
            @confirmar="apagar"
            @cancelar="aberta = null"
        />
    </div>
</template>
