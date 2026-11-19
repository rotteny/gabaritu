<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import GrupoOpcoes from '@/components/aluno/GrupoOpcoes.vue';
import InterruptorPref from '@/components/aluno/InterruptorPref.vue';
import { rotas } from '@/lib/rotas';
import type { Preferencias } from '@/types/gabaritu';

const props = defineProps<{ preferencias: Preferencias }>();

const zoeira = ref<string>(props.preferencias.zoeira);
const som = ref(props.preferencias.som);
const musica = ref(props.preferencias.musica);
const reduzir = ref(props.preferencias.reduzir_movimento);
const estado = ref<'' | 'salvando' | 'salvo' | 'erro'>('');

const niveis = computed(() => {
    const base = [
        { valor: 'fofo', rotulo: 'Fofo' },
        { valor: 'sarc', rotulo: 'Sarcástico' },
    ];

    return props.preferencias.zoeira_maxima === 'sem'
        ? [...base, { valor: 'sem', rotulo: 'Sem filtro' }]
        : base;
});

/** Salva na hora, sem botão: cada mudança vai direto pro servidor. */
function salvar(dados: Record<string, string | boolean>) {
    estado.value = 'salvando';
    router.patch(rotas.conta + '/preferencias', dados, {
        preserveScroll: true,
        preserveState: true,
        only: ['auth', 'errors'],
        onSuccess: () => (estado.value = 'salvo'),
        onError: () => (estado.value = 'erro'),
    });
}

const mudarZoeira = (v: string) => {
    zoeira.value = v;
    salvar({ zoeira: v });
};
const mudarSom = (v: boolean) => {
    som.value = v;
    salvar({ som: v });
};
const mudarMusica = (v: boolean) => {
    musica.value = v;
    salvar({ musica: v });
};
const mudarMovimento = (v: boolean) => {
    reduzir.value = v;
    salvar({ reduzir_movimento: v });
};
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="pref-titulo">
        <h2 id="pref-titulo" class="gb-subtitulo">Preferências</h2>
        <GrupoOpcoes
            :model-value="zoeira"
            legenda="Zoeira do Peixoto"
            :opcoes="niveis"
            @update:model-value="mudarZoeira"
        />
        <p class="gb-ajuda">
            Fofo é carinho com piada. Sarcástico é ironia na medida. O nível
            "sem filtro" só existe a partir de 15 anos.
        </p>
        <InterruptorPref
            :model-value="som"
            rotulo="Som"
            ajuda="Bipes do Peixoto e efeitos de acerto e erro."
            @update:model-value="mudarSom"
        />
        <InterruptorPref
            :model-value="musica"
            rotulo="Música de fundo"
            ajuda="Uma musiquinha calma de aquário, baixinha. Só toca com o som ligado."
            @update:model-value="mudarMusica"
        />
        <InterruptorPref
            :model-value="reduzir"
            rotulo="Reduzir movimento"
            ajuda="Menos animação: sem tremedeira, explosão e texto digitando."
            @update:model-value="mudarMovimento"
        />
        <p class="gb-ajuda" role="status" aria-live="polite">
            <template v-if="estado === 'salvando'">Salvando...</template>
            <template v-else-if="estado === 'salvo'">Salvo.</template>
            <template v-else-if="estado === 'erro'"
                >Não consegui salvar. Tente de novo.</template
            >
        </p>
    </section>
</template>
