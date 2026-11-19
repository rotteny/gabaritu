<script setup lang="ts">
import { Mail } from '@lucide/vue';

defineProps<{ emailContato: string }>();
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
            <p class="l-footer-small">
                Gabaritu. Código sob licença MIT, conteúdo sob CC BY-SA. Nenhum
                baiacu foi ofendido na produção desta página.
            </p>
        </div>
    </footer>
</template>
