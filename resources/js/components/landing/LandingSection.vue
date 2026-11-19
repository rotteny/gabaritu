<script setup lang="ts">
defineProps<{ id: string; titulo: string; lead?: string; escura?: boolean }>();
</script>

<template>
    <section
        :id="id"
        class="l-section"
        :class="{ 'l-section--dark': escura }"
        :aria-labelledby="`${id}-titulo`"
    >
        <div class="l-wrap">
            <h2 :id="`${id}-titulo`" class="l-title">{{ titulo }}</h2>
            <p v-if="lead" class="l-lead">{{ lead }}</p>
            <slot />
        </div>
    </section>
</template>
