<script setup lang="ts">
import GraficoEvolucao from '@/components/aluno/GraficoEvolucao.vue';
import { formatarNota } from '@/lib/area';
import type { DesempenhoAssunto } from '@/types/gabaritu';

defineProps<{ item: DesempenhoAssunto; nivel: 2 | 3 }>();
</script>

<template>
    <div class="gb-desempenho">
        <header class="gb-assunto-topo">
            <component :is="`h${nivel}`" class="gb-subtitulo">{{
                item.nome
            }}</component>
            <p v-if="item.nota !== null" class="gb-nota">
                {{ formatarNota(item.nota) }}
                <span class="gb-miudo">nota estimada</span>
            </p>
            <p v-else class="gb-miudo">
                Sem nota ainda: faltam respostas nesse assunto.
            </p>
            <span
                v-if="item.amostra_baixa && item.nota !== null"
                class="gb-tag gb-tag--cinza"
            >
                pouca amostra
            </span>
        </header>
        <GraficoEvolucao
            v-if="item.evolucao.length > 0"
            :pontos="item.evolucao"
            :titulo="item.nome"
        />
        <ul v-if="item.diagnostico.length > 0" class="gb-diagnostico">
            <li v-for="d in item.diagnostico" :key="d.etiqueta">
                <span class="gb-tag">{{ d.percentual }}%</span>
                dos seus erros foram por <strong>{{ d.nome }}</strong>
            </li>
        </ul>
        <p v-else-if="item.nota !== null" class="gb-miudo">
            Ainda pouco dado pra um diagnóstico.
        </p>
    </div>
</template>
