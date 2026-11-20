<script setup lang="ts">
import Aviso from '@/components/aluno/Aviso.vue';
import { nomeDoVinculo } from '@/lib/responsavel';
import type { ConviteDoCadastro } from '@/types/gabaritu';

defineProps<{ convite: ConviteDoCadastro; erro?: string }>();
const confirma = defineModel<boolean>('confirma', { required: true });
</script>

<template>
    <fieldset class="gb-form gb-fieldset-resp">
        <legend class="gb-legenda">Convite de responsável</legend>
        <Aviso tipo="info">
            <strong>{{ convite.responsavel.nome }}</strong>
            ({{ nomeDoVinculo(convite) }}) quer ser o seu responsável no
            Gabaritu. Use o e-mail {{ convite.email }} no cadastro.
        </Aviso>
        <label class="gb-check">
            <input v-model="confirma" type="checkbox" />
            <span>
                Confirmo que {{ convite.responsavel.nome }} é meu responsável.
                Se eu tiver menos de 18 anos, não preciso esperar outra
                autorização.
            </span>
        </label>
        <p v-if="erro" class="gb-erro" role="alert">{{ erro }}</p>
    </fieldset>
</template>
