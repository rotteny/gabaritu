<script setup lang="ts">
defineProps<{ ativa: boolean }>();

const gotas = [
    { x: '8%', h: '34px', d: '0s' },
    { x: '24%', h: '58px', d: '.12s' },
    { x: '41%', h: '28px', d: '.05s' },
    { x: '58%', h: '70px', d: '.2s' },
    { x: '76%', h: '40px', d: '.08s' },
    { x: '91%', h: '52px', d: '.16s' },
];
</script>

<template>
    <div class="l-slime" :class="{ 'is-on': ativa }" aria-hidden="true">
        <span
            v-for="g in gotas"
            :key="g.x"
            class="l-slime-drip"
            :style="{ '--x': g.x, '--h': g.h, '--d': g.d }"
        />
    </div>
</template>
