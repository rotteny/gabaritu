<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmarAcao from '@/components/responsavel/ConfirmarAcao.vue';
import { rotas } from '@/lib/rotas';
import { nomeDoVinculo } from '@/lib/responsavel';
import type { ResponsavelDoAluno } from '@/types/gabaritu';

const props = defineProps<{
    alunoId: number;
    apelido: string;
    responsavel: ResponsavelDoAluno & { id: number };
    podeRestaurar?: boolean;
}>();

const removendo = ref(false);
const erro = ref('');
const restaurar = () =>
    router.post(
        rotas.restaurarSuspenso(props.alunoId, props.responsavel.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (erro.value = ''),
            onError: (erros) =>
                (erro.value =
                    erros.vinculo ?? 'Não deu para restaurar o vínculo.'),
        },
    );
const remover = () =>
    router.delete(rotas.removerSuspenso(props.alunoId, props.responsavel.id), {
        preserveScroll: true,
        onFinish: () => (removendo.value = false),
    });
</script>

<template>
    <div class="gb-aviso gb-aviso--erro gb-suspenso" role="group">
        <p class="gb-texto">
            {{ apelido }} disse que <strong>{{ responsavel.nome }}</strong> ({{
                nomeDoVinculo(responsavel)
            }}) não é responsável por ele. O acesso dessa pessoa está suspenso.
            Você confirma ou desfaz?
        </p>
        <div class="gb-acoes-aluno">
            <button
                type="button"
                class="l-btn l-btn--perigo gb-btn-mini"
                @click="removendo = true"
            >
                Confirmar remoção
            </button>
            <button
                v-if="podeRestaurar !== false"
                type="button"
                class="l-btn l-btn--white gb-btn-mini"
                @click="restaurar"
            >
                Restaurar o vínculo
            </button>
        </div>
        <p v-if="podeRestaurar === false" class="gb-miudo">
            {{ apelido }} tem 18 anos ou mais: o vínculo contestado não pode ser
            restaurado.
        </p>
        <p v-if="erro" class="gb-aviso gb-aviso--erro" role="alert">
            {{ erro }}
        </p>
        <ConfirmarAcao
            v-if="removendo"
            titulo="Remover essa pessoa?"
            :texto="`${responsavel.nome} deixa de acompanhar ${apelido}. Isso não pode ser desfeito.`"
            rotulo-confirmar="Sim, remover"
            @confirmar="remover"
            @cancelar="removendo = false"
        />
    </div>
</template>
