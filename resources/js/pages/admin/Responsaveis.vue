<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useId } from 'vue';
import CabecalhoPagina from '@/components/admin/CabecalhoPagina.vue';
import EstadoVazio from '@/components/admin/EstadoVazio.vue';
import Paginacao from '@/components/admin/Paginacao.vue';
import { useFiltros } from '@/composables/useFiltros';
import type { Paginado, ResponsavelLinha } from '@/types/admin';

const props = defineProps<{
    filtros: { busca: string | null };
    responsaveis: Paginado<ResponsavelLinha>;
}>();
const { filtros, aplicar, limpar } = useFiltros({ ...props.filtros });
const idBusca = useId();
</script>

<template>
    <CabecalhoPagina
        titulo="Responsáveis"
        subtitulo="Só leitura. O e-mail aparece mascarado."
    />
    <form
        class="ad-cartao ad-filtros"
        role="search"
        @submit.prevent="aplicar()"
    >
        <div class="ad-campo ad-campo--busca">
            <label :for="idBusca">Buscar por e-mail</label>
            <input
                :id="idBusca"
                v-model="filtros.busca"
                class="ad-input"
                type="search"
                placeholder="E-mail completo"
            />
        </div>
        <button type="submit" class="ad-btn">Buscar</button>
        <button type="button" class="ad-btn ad-btn--claro" @click="limpar">
            Limpar
        </button>
    </form>
    <EstadoVazio
        v-if="responsaveis.dados.length === 0"
        titulo="Nenhum responsável encontrado"
    >
        Digite o e-mail completo: a busca por e-mail é exata.
    </EstadoVazio>
    <template v-else>
        <div class="ad-rolagem">
            <table class="ad-tabela">
                <caption class="ad-sr">
                    Lista de responsáveis
                </caption>
                <thead>
                    <tr>
                        <th scope="col">E-mail</th>
                        <th scope="col" class="ad-dir">Alunos</th>
                        <th scope="col" class="ad-dir">Convites pendentes</th>
                        <th scope="col">Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in responsaveis.dados" :key="r.id">
                        <td>
                            <Link :href="r.url" class="ad-link">{{
                                r.email_mascarado
                            }}</Link>
                        </td>
                        <td class="ad-dir">{{ r.alunos_vinculados }}</td>
                        <td class="ad-dir">{{ r.convites_pendentes }}</td>
                        <td class="ad-num">{{ r.cadastro }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Paginacao :paginado="responsaveis" />
    </template>
</template>
