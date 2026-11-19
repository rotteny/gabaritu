<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';

withDefaults(defineProps<{ compacto?: boolean }>(), { compacto: false });

const sair = () => router.post('/logout');
</script>

<template>
    <button
        type="button"
        class="gb-sair"
        :class="{ 'gb-sair--compacto': compacto }"
        @click="sair"
    >
        <LogOut :size="20" aria-hidden="true" />
        <span class="gb-sair-rotulo">Sair</span>
    </button>
</template>
