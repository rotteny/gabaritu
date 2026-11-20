<script setup lang="ts">
import '../../css/admin/admin.css';
</script>

<template>
    <div class="ad">
        <main class="ad-auth">
            <div class="ad-auth-cartao">
                <p class="ad-marca" aria-label="Gabaritu, painel admin">
                    Gabaritu <small>Painel admin</small>
                </p>
                <slot />
            </div>
        </main>
    </div>
</template>
