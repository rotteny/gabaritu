<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Volume2, VolumeX, X } from '@lucide/vue';
import MarcaGabaritu from '@/components/MarcaGabaritu.vue';
import BotaoMusica from '@/components/BotaoMusica.vue';
import { rotas } from '@/lib/rotas';
import type { Tentativa } from '@/types/gabaritu';

defineProps<{ tentativa: Tentativa }>();
</script>

<template>
    <header class="q-hud">
        <MarcaGabaritu tamanho="sm" class="q-hud-marca" />
        <div class="q-hud-titulo">
            <h1 class="q-h1">{{ tentativa.titulo }}</h1>
            <p class="q-hud-sub">
                {{ tentativa.materia.nome }} · {{ tentativa.assunto.nome }}
                <template v-if="tentativa.tipo === 'revisao'">
                    · Revisão</template
                >
            </p>
        </div>
        <div
            class="q-progresso"
            role="img"
            :aria-label="`Questão ${tentativa.posicao} de ${tentativa.total}`"
        >
            <span class="q-hud-rotulo" aria-hidden="true">
                Questão <b>{{ tentativa.posicao }}</b> de
                <b>{{ tentativa.total }}</b>
            </span>
            <span class="q-segs" aria-hidden="true">
                <i
                    v-for="n in tentativa.total"
                    :key="n"
                    :class="{
                        'is-feita': n < tentativa.posicao,
                        'is-atual': n === tentativa.posicao,
                    }"
                />
            </span>
        </div>
        <div class="q-hud-acoes">
            <BotaoSom />
            <Link
                :href="rotas.inicio"
                class="l-icon-btn"
                aria-label="Sair do questionário e voltar pro início"
            >
                <X :size="22" aria-hidden="true" />
            </Link>
        </div>
    </header>
</template>
