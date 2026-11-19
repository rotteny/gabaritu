<script setup lang="ts">
import { Mail } from '@lucide/vue';
import LinhaCredito from '@/components/LinhaCredito.vue';

defineProps<{ emailContato: string; repositorioUrl: string }>();
</script>

<template>
    <footer class="l-footer">
        <div class="l-wrap l-footer-grid">
            <div>
                <h2 class="l-h3">Fala com a gente</h2>
                <a :href="`mailto:${emailContato}`" class="l-mail">
                    <Mail class="size-5" aria-hidden="true" />{{ emailContato }}
                </a>
            </div>
            <LinhaCredito :repositorio-url="repositorioUrl" />
        </div>
    </footer>
</template>
