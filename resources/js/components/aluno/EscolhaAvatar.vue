<script setup lang="ts">
import { useId } from 'vue';
import AvatarAluno from '@/components/aluno/AvatarAluno.vue';
import { AVATARES } from '@/data/avatares';

defineProps<{ legenda: string; ajuda?: string; erro?: string }>();

/** Vazio quando ninguém foi escolhido (o backend sorteia). */
const modelo = defineModel<string>({ required: true });
const nome = useId();
</script>

<template>
    <fieldset class="gb-grupo">
        <legend class="gb-legenda">{{ legenda }}</legend>
        <p v-if="ajuda" class="gb-ajuda">{{ ajuda }}</p>
        <div class="gb-avatares">
            <label v-for="a in AVATARES" :key="a.slug" class="gb-avatar-opcao">
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
