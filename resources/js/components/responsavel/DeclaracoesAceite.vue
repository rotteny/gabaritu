<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { rotas } from '@/lib/rotas';

defineProps<{ erroDeclara?: string; erroAceite?: string }>();
const declara = defineModel<boolean>('declara', { required: true });
const aceite = defineModel<boolean>('aceite', { required: true });
</script>

<template>
    <div class="gb-form">
        <div>
            <label class="gb-check">
                <input v-model="declara" type="checkbox" />
                <span>
                    Sou responsável legal por este aluno (pai, mãe, avó/avô,
                    tutor ou guardião com a guarda).
                </span>
            </label>
            <p v-if="erroDeclara" class="gb-erro" role="alert">
                {{ erroDeclara }}
            </p>
        </div>
        <div>
            <label class="gb-check">
                <input v-model="aceite" type="checkbox" />
                <span>
                    Li e aceito os
                    <Link :href="rotas.termos" class="gb-link" target="_blank"
                        >Termos de uso</Link
                    >
                    e a
                    <Link
                        :href="rotas.privacidade"
                        class="gb-link"
                        target="_blank"
                        >Política de Privacidade</Link
                    >.
                </span>
            </label>
            <p v-if="erroAceite" class="gb-erro" role="alert">
                {{ erroAceite }}
            </p>
        </div>
    </div>
</template>
