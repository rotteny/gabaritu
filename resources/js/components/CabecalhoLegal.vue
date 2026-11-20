<script setup lang="ts">
import { DATA_TEXTOS, VERSAO_TEXTOS } from '@/lib/responsavel';
import type { SecaoLegal } from '@/types/legal';

defineProps<{ titulo: string; prefixo: string; secoes: SecaoLegal[] }>();
</script>

<template>
    <header class="gb-form">
        <h1 class="gb-titulo">{{ titulo }}</h1>
        <p class="gb-legal-meta">
            <span class="gb-tag">Versão {{ VERSAO_TEXTOS }}</span>
            <span class="gb-miudo">Atualizado em {{ DATA_TEXTOS }}</span>
        </p>
        <nav aria-label="Nesta página">
            <ul class="gb-legal-indice">
                <li v-for="s in secoes" :key="s.id">
                    <a :href="`#${prefixo}-${s.id}`" class="gb-link">{{
                        s.titulo
                    }}</a>
                </li>
            </ul>
        </nav>
    </header>
</template>
