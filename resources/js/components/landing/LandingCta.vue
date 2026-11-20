<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { login, register } from '@/routes';

defineProps<{ cadastroAberto: boolean; repositorioUrl: string }>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <template v-if="cadastroAberto">
            <Link :href="register()" class="l-btn l-btn--orange"
                >Criar conta</Link
            >
            <Link :href="login()" class="l-btn l-btn--white">Entrar</Link>
        </template>
        <template v-else>
            <span class="l-badge">Em breve: o cadastro abre logo!</span>
            <a
                :href="repositorioUrl"
                class="l-btn l-btn--yellow"
                target="_blank"
                rel="noopener noreferrer"
            >
                Ver o código no GitHub
                <ExternalLink class="size-5" aria-hidden="true" />
                <span class="sr-only">(abre em nova aba)</span>
            </a>
        </template>
    </div>
</template>
