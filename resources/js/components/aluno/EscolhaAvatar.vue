<script setup lang="ts">
import { computed, useId } from 'vue';
import AvatarAluno from '@/components/aluno/AvatarAluno.vue';
import { AVATARES, nomeDoAvatar } from '@/data/avatares';

const props = defineProps<{
    legenda: string;
    ajuda?: string;
    erro?: string;
    /** Slugs disponíveis (vêm do backend); sem ela, usa a lista local. */
    avatares?: readonly string[];
}>();

const opcoes = computed(() =>
    props.avatares
        ? props.avatares.map((slug) => ({ slug, nome: nomeDoAvatar(slug) }))
        : AVATARES,
);

/** Vazio quando ainda não há escolha. */
const modelo = defineModel<string>({ required: true });
const nome = useId();
</script>

<template>
    <fieldset class="gb-grupo">
        <legend class="gb-legenda">{{ legenda }}</legend>
        <p v-if="ajuda" class="gb-ajuda">{{ ajuda }}</p>
        <div class="gb-avatares">
            <label v-for="a in opcoes" :key="a.slug" class="gb-avatar-opcao">
                <input
                    v-model="modelo"
                    type="radio"
                    :name="nome"
                    :value="a.slug"
                />
                <AvatarAluno :slug="a.slug" :tamanho="72" decorativo />
                <span class="gb-avatar-nome">{{ a.nome }}</span>
            </label>
        </div>
        <p v-if="erro" class="gb-erro" role="alert">{{ erro }}</p>
    </fieldset>
</template>
