<script setup lang="ts">
import type { SecaoLegal } from '@/types/legal';

defineProps<{ secoes: SecaoLegal[]; prefixo: string }>();
</script>

<template>
    <section
        v-for="s in secoes"
        :key="s.id"
        class="gb-cartao gb-prosa gb-legal"
        :aria-labelledby="`${prefixo}-${s.id}`"
    >
        <h2 :id="`${prefixo}-${s.id}`" class="gb-subtitulo">{{ s.titulo }}</h2>
        <p v-for="p in s.paragrafos ?? []" :key="p" class="gb-texto">{{ p }}</p>
        <ul v-if="s.itens" class="gb-lista-simples">
            <li v-for="i in s.itens" :key="i">{{ i }}</li>
        </ul>
        <dl v-if="s.definicoes" class="gb-definicoes">
            <template v-for="[t, d] in s.definicoes" :key="t">
                <dt>{{ t }}</dt>
                <dd>{{ d }}</dd>
            </template>
        </dl>
        <slot :name="s.id" />
    </section>
</template>
