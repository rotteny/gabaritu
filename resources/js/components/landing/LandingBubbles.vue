<script setup lang="ts">
const bolhas = [
    { x: '6%', s: '22px', t: '11s', d: '0s' },
    { x: '18%', s: '14px', t: '9s', d: '3s' },
    { x: '33%', s: '30px', t: '14s', d: '1s' },
    { x: '52%', s: '16px', t: '10s', d: '5s' },
    { x: '68%', s: '26px', t: '13s', d: '2s' },
    { x: '82%', s: '12px', t: '8s', d: '6s' },
    { x: '93%', s: '24px', t: '12s', d: '4s' },
];
</script>

<template>
    <div class="l-bubbles" aria-hidden="true">
        <span
            v-for="b in bolhas"
            :key="b.x"
            class="l-bubble"
            :style="{ '--x': b.x, '--s': b.s, '--t': b.t, '--d': b.d }"
        />
    </div>
</template>
