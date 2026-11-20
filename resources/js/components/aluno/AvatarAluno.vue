<script setup lang="ts">
import { computed } from 'vue';
import { nomeDoAvatar } from '@/data/avatares';

const props = withDefaults(
    defineProps<{
        slug?: string | null;
        tamanho?: number;
        /** Quando o nome do avatar já está escrito ao lado, o avatar é só decorativo. */
        decorativo?: boolean;
    }>(),
    { slug: null, tamanho: 48, decorativo: false },
);

const alt = computed(() =>
    props.decorativo || !props.slug ? '' : nomeDoAvatar(props.slug),
);
</script>

<template>
    <img
        v-if="slug"
        class="gb-avatar"
        :src="`/avatares/${slug}.svg`"
        :alt="alt"
        :width="tamanho"
        :height="tamanho"
    />
</template>
