<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import type { DistribuicaoItem, QuestaoLinha } from '@/types/admin';

const props = defineProps<{
    questao: QuestaoLinha & {
        dificuldade: number;
        status: string;
        contexto: string | null;
        enunciado: string;
        explicacao: string | null;
        imagem_url: string | null;
        fonte: string | null;
        distribuicao: (DistribuicaoItem & {
            texto: string | null;
            etiqueta: string | null;
        })[];
        historico_juridico: {
            de: string | null;
            para: string | null;
            quando: string;
            admin: string;
        }[];
    };
    opcoes: { status_juridico: string[] };
    acoes: { status_juridico: string | null; voltar: string };
}>();

const novo = ref(props.questao.status_juridico ?? 'pendente');
const salvando = ref(false);

function salvar() {
    if (!props.acoes.status_juridico) {
        return;
    }

    if (
        novo.value === 'remover' &&
        !window.confirm('Remover tira a questão do ar na hora. Confirmar?')
    ) {
        return;
    }

    router.patch(
        props.acoes.status_juridico,
        { status: novo.value },
        {
            preserveScroll: true,
            onStart: () => (salvando.value = true),
            onFinish: () => (salvando.value = false),
        },
    );
}
</script>

<template>
    <CabecalhoPagina
        :titulo="`Questão ${questao.codigo}`"
        :subtitulo="`${questao.materia ?? ''} · ${questao.assunto ?? ''}`"
    >
        <Link :href="acoes.voltar" class="ad-btn ad-btn--claro"
            >Voltar à lista</Link
        >
    </CabecalhoPagina>

    <section class="ad-cartao">
        <dl class="ad-dl">
            <div>
                <dt>Origem</dt>
                <dd>{{ questao.origem === 'inep' ? 'INEP' : 'Autoral' }}</dd>
            </div>
            <div>
                <dt>Respostas</dt>
                <dd>{{ questao.respostas }}</dd>
            </div>
            <div>
                <dt>Acerto</dt>
                <dd>
                    {{
                        questao.pct_acerto === null
                            ? '-'
                            : `${questao.pct_acerto}%`
                    }}
                </dd>
            </div>
            <div>
                <dt>Reports abertos</dt>
                <dd>{{ questao.reports_abertos }}</dd>
            </div>
            <div>
                <dt>Dificuldade</dt>
                <dd>{{ questao.dificuldade }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>{{ questao.status }}</dd>
            </div>
        </dl>
    </section>

    <section v-if="acoes.status_juridico" class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Status jurídico (INEP)</h2>
        <form class="ad-filtros" @submit.prevent="salvar">
            <div class="ad-campo">
                <label for="ad-sj">Status</label>
                <select id="ad-sj" v-model="novo" class="ad-input">
                    <option
                        v-for="s in opcoes.status_juridico"
                        :key="s"
                        :value="s"
                    >
                        {{ s }}
                    </option>
                </select>
            </div>
            <button type="submit" class="ad-btn" :disabled="salvando">
                Salvar
            </button>
        </form>
        <ul v-if="questao.historico_juridico.length" class="ad-lista">
            <li
                v-for="(h, i) in questao.historico_juridico"
                :key="i"
                class="ad-mudo"
            >
                {{ h.quando }}: <TagStatus :valor="h.de" /> para
                <TagStatus :valor="h.para" /> por {{ h.admin }}
            </li>
        </ul>
    </section>

    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Enunciado</h2>
        <p v-if="questao.contexto" class="ad-texto-q ad-mudo">
            {{ questao.contexto }}
        </p>
        <img
            v-if="questao.imagem_url"
            :src="questao.imagem_url"
            alt="Imagem da questão"
            class="ad-qr"
        />
        <p class="ad-texto-q">{{ questao.enunciado }}</p>
        <p v-if="questao.fonte" class="ad-dica">Fonte: {{ questao.fonte }}</p>
    </section>

    <section class="ad-cartao">
        <h2 class="ad-h2 ad-cartao-cab">Alternativas e distribuição</h2>
        <ul class="ad-lista">
            <li v-for="d in questao.distribuicao" :key="d.letra">
                <strong>{{ d.letra }}</strong>
                <span v-if="d.correta" class="ad-tag ad-tag--ok">correta</span>
                {{ d.texto }}
                <span class="ad-mudo ad-num">
                    · {{ d.total }} respostas ({{
                        d.pct === null ? '-' : `${d.pct}%`
                    }})</span
                >
                <span v-if="d.etiqueta" class="ad-tag"> {{ d.etiqueta }}</span>
            </li>
        </ul>
        <p v-if="questao.explicacao" class="ad-texto-q ad-dica">
            Explicação: {{ questao.explicacao }}
        </p>
    </section>
</template>
