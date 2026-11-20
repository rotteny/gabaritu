<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Clock,
    Flag,
    HeartPulse,
    LayoutDashboard,
    ListChecks,
    LogOut,
    MessageSquareQuote,
    Repeat,
    ShieldCheck,
    SquareCheckBig,
    Users,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import type { AdminCompartilhado } from '@/types/admin';

const props = defineProps<{
    admin: AdminCompartilhado;
    aberto: boolean;
    caminho: string;
    rail: boolean;
}>();
defineEmits<{ fechar: [] }>();

const grupos = computed(() => {
    const r = props.admin.rotas;

    return [
        {
            nome: 'Visão geral',
            itens: [
                {
                    rotulo: 'Dashboard',
                    href: r.dashboard,
                    icone: LayoutDashboard,
                },
                { rotulo: 'Respostas', href: r.respostas, icone: ListChecks },
                { rotulo: 'Retenção', href: r.retencao, icone: Repeat },
            ],
        },
        {
            nome: 'Pessoas',
            itens: [
                { rotulo: 'Alunos', href: r.alunos, icone: Users },
                {
                    rotulo: 'Responsáveis',
                    href: r.responsaveis,
                    icone: ShieldCheck,
                },
                {
                    rotulo: 'Pendências legais',
                    href: r.pendencias,
                    icone: Clock,
                },
            ],
        },
        {
            nome: 'Conteúdo',
            itens: [
                { rotulo: 'Questões', href: r.questoes, icone: SquareCheckBig },
                { rotulo: 'Reports', href: r.reports, icone: Flag },
                {
                    rotulo: 'Falas do Peixoto',
                    href: r.falas,
                    icone: MessageSquareQuote,
                },
            ],
        },
        {
            nome: 'Sistema',
            itens: [{ rotulo: 'Saúde', href: r.saude, icone: HeartPulse }],
        },
    ];
});

function ativo(href: string): boolean {
    const base = new URL(href, 'http://x').pathname;
    const raiz = new URL(props.admin.rotas.dashboard, 'http://x').pathname;

    return base === raiz
        ? props.caminho === base || props.caminho === `${base}/`
        : props.caminho === base || props.caminho.startsWith(`${base}/`);
}

function sair() {
    router.post(props.admin.rotas.sair);
}
</script>

<template>
    <aside id="ad-menu" class="ad-side" :data-aberta="aberto">
        <div class="ad-side-topo">
            <Link :href="admin.rotas.dashboard" class="ad-marca">
                Gabaritu <small>Painel admin</small>
            </Link>
            <button
                type="button"
                class="ad-btn ad-btn--claro ad-btn--icone ad-btn-menu"
                aria-label="Fechar menu"
                @click="$emit('fechar')"
            >
                <X class="ad-ico" aria-hidden="true" />
            </button>
        </div>
        <nav class="ad-nav" aria-label="Painel admin">
            <template v-for="grupo in grupos" :key="grupo.nome">
                <p class="ad-nav-grupo">{{ grupo.nome }}</p>
                <Link
                    v-for="item in grupo.itens"
                    :key="item.href"
                    :href="item.href"
                    :aria-current="ativo(item.href) ? 'page' : undefined"
                    :title="rail ? item.rotulo : undefined"
                >
                    <component
                        :is="item.icone"
                        class="ad-ico"
                        aria-hidden="true"
                    />
                    <span class="ad-rotulo">{{ item.rotulo }}</span>
                </Link>
            </template>
        </nav>
        <div class="ad-nav ad-nav--fim">
            <button
                type="button"
                :title="rail ? 'Sair' : undefined"
                @click="sair"
            >
                <LogOut class="ad-ico" aria-hidden="true" />
                <span class="ad-rotulo">Sair</span>
            </button>
        </div>
        <p class="ad-side-pe">{{ admin.email }}</p>
    </aside>
</template>
