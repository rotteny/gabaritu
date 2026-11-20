<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useId } from 'vue';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import Paginacao from '@/components/admin/Paginacao.vue';
import TagStatus from '@/components/admin/TagStatus.vue';
import { useFiltros } from '@/composables/useFiltros';
import type { AlunoLinha, Paginado } from '@/types/admin';

const props = defineProps<{
    filtros: {
        busca: string | null;
        status: string | null;
        serie: string | null;
        tem_responsavel: string | null;
    };
    opcoes: { status: string[]; series: { valor: string; rotulo: string }[] };
    alunos: Paginado<AlunoLinha>;
}>();

const { filtros, aplicar, limpar } = useFiltros({ ...props.filtros });
const idBusca = useId();
const idStatus = useId();
const idSerie = useId();
const idResp = useId();
</script>

<template>
    <CabecalhoPagina
        titulo="Alunos"
        subtitulo="Só leitura. O e-mail aparece mascarado."
    />
    <form
        class="ad-cartao ad-filtros"
        role="search"
        @submit.prevent="aplicar()"
    >
        <div class="ad-campo ad-campo--busca">
            <label :for="idBusca">Buscar</label>
            <input
                :id="idBusca"
                v-model="filtros.busca"
                class="ad-input"
                type="search"
                placeholder="Apelido (começa com) ou e-mail completo"
            />
        </div>
        <div class="ad-campo">
            <label :for="idStatus">Status</label>
            <select
                :id="idStatus"
                v-model="filtros.status"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Todos</option>
                <option v-for="s in opcoes.status" :key="s" :value="s">
                    {{ s === 'ativo' ? 'Ativo' : 'Aguardando responsável' }}
                </option>
            </select>
        </div>
        <div class="ad-campo">
            <label :for="idSerie">Série</label>
            <select
                :id="idSerie"
                v-model="filtros.serie"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Todas</option>
                <option
                    v-for="s in opcoes.series"
                    :key="s.valor"
                    :value="s.valor"
                >
                    {{ s.rotulo }}
                </option>
            </select>
        </div>
        <div class="ad-campo">
            <label :for="idResp">Responsável</label>
            <select
                :id="idResp"
                v-model="filtros.tem_responsavel"
                class="ad-input"
                @change="aplicar()"
            >
                <option :value="null">Tanto faz</option>
                <option value="sim">Tem</option>
                <option value="nao">Não tem</option>
            </select>
        </div>
        <button type="submit" class="ad-btn">Buscar</button>
        <button type="button" class="ad-btn ad-btn--claro" @click="limpar">
            Limpar
        </button>
    </form>

    <EstadoVazio
        v-if="alunos.dados.length === 0"
        titulo="Nenhum aluno encontrado"
    >
        Ajuste os filtros ou limpe a busca.
    </EstadoVazio>
    <template v-else>
        <div class="ad-rolagem">
            <table class="ad-tabela">
                <caption class="ad-sr">
                    Lista de alunos
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Apelido</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Série</th>
                        <th scope="col">Idade</th>
                        <th scope="col">Status</th>
                        <th scope="col">Cadastro</th>
                        <th scope="col">Última atividade</th>
                        <th scope="col" class="ad-dir">Respostas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in alunos.dados" :key="a.id">
                        <td>
                            <Link :href="a.url" class="ad-link">{{
                                a.apelido
                            }}</Link>
                        </td>
                        <td>{{ a.email_mascarado }}</td>
                        <td>{{ a.serie_rotulo ?? '-' }}</td>
                        <td class="ad-num">{{ a.idade ?? '-' }}</td>
                        <td><TagStatus :valor="a.status" /></td>
                        <td class="ad-num">{{ a.cadastro }}</td>
                        <td class="ad-num">{{ a.ultima_atividade ?? '-' }}</td>
                        <td class="ad-dir">{{ a.respostas }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Paginacao :paginado="alunos" />
    </template>
</template>
