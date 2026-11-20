<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Fish } from '@lucide/vue';
import LinkExterno from '@/components/LinkExterno.vue';
import { AUTOR_URL } from '@/lib/constantes';
import { rotas } from '@/lib/rotas';

defineProps<{ repositorioUrl: string }>();
</script>

<template>
    <p class="credito">
        feito com <Fish class="credito-icone" aria-hidden="true" /> por
        <LinkExterno :href="AUTOR_URL">rotteny</LinkExterno>
        ·
        <LinkExterno :href="repositorioUrl">código aberto</LinkExterno> no
        GitHub ·
        <Link :href="rotas.quemSomos">Quem somos</Link>
    </p>
</template>
