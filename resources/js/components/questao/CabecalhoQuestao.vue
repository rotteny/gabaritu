<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Volume2, VolumeX, X } from '@lucide/vue';
import { rotas } from '@/lib/rotas';
import type { Tentativa } from '@/types/gabaritu';

defineProps<{ tentativa: Tentativa; somLigado: boolean }>();
defineEmits<{ 'alternar-som': [] }>();
</script>

<template>
    <header class="q-hud">
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
            <button
                type="button"
                class="l-icon-btn"
                :aria-pressed="somLigado"
                :aria-label="
                    somLigado
                        ? 'Som ligado. Desligar o som'
                        : 'Som desligado. Ligar o som'
                "
                @click="$emit('alternar-som')"
            >
                <Volume2 v-if="somLigado" :size="22" aria-hidden="true" />
                <VolumeX v-else :size="22" aria-hidden="true" />
            </button>
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
