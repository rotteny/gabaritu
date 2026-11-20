<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { rotas } from '@/lib/rotas';
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
                <p class="l-resp-link">
                    <Link :href="rotas.entrarResponsavel">Sou responsável</Link>
                </p>
            </div>
            <LinhaCredito :repositorio-url="repositorioUrl" />
        </div>
    </footer>
</template>
