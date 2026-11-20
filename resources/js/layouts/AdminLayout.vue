<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import MenuAdmin from '@/components/admin/MenuAdmin.vue';
import { useAdmin } from '@/composables/useAdmin';
import { Menu, PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import '../../css/admin/admin.css';

const { admin } = useAdmin();
const page = usePage();
const menuAberto = ref(false);
const rail = ref(false);
const caminho = computed(() => page.url.split('?')[0]);

watch(caminho, () => {
    menuAberto.value = false;
});
</script>

<template>
    <div class="ad" :data-rail="rail">
        <a href="#conteudo" class="ad-skip">Pular para o conteúdo</a>
        <div class="ad-shell">
            <MenuAdmin
                v-if="admin"
                :admin="admin"
                :aberto="menuAberto"
                :caminho="caminho"
                :rail="rail"
                @fechar="menuAberto = false"
            />
            <button
                v-if="menuAberto"
                type="button"
                class="ad-veu"
                aria-label="Fechar menu"
                @click="menuAberto = false"
            />
            <div class="ad-main">
                <header class="ad-topo">
                    <button
                        type="button"
                        class="ad-btn ad-btn--claro ad-btn--icone ad-btn-menu"
                        :aria-expanded="menuAberto"
                        aria-controls="ad-menu"
                        aria-label="Abrir menu"
                        @click="menuAberto = true"
                    >
                        <Menu class="ad-ico" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        class="ad-btn ad-btn--claro ad-btn--icone ad-btn-rail"
                        :aria-pressed="rail"
                        :aria-label="rail ? 'Expandir menu' : 'Recolher menu'"
                        @click="rail = !rail"
                    >
                        <component
                            :is="rail ? PanelLeftOpen : PanelLeftClose"
                            class="ad-ico"
                            aria-hidden="true"
                        />
                    </button>
                    <Link
                        v-if="admin"
                        :href="admin.rotas.dashboard"
                        class="ad-marca"
                        >Gabaritu</Link
                    >
                </header>
                <div
                    v-if="admin?.restantes_recuperacao !== null && admin"
                    class="ad-aviso-sessao"
                    role="status"
                >
                    Você entrou com um código de recuperação. Restam
                    {{ admin.restantes_recuperacao }}.
                </div>
                <main id="conteudo" class="ad-conteudo" tabindex="-1">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
