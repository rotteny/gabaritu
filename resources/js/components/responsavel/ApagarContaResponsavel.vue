<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Aviso from '@/components/aluno/Aviso.vue';
import ConfirmarAcao from '@/components/responsavel/ConfirmarAcao.vue';
import { rotas } from '@/lib/rotas';

defineProps<{ bloqueadoPor: string[] }>();

const aberto = ref(false);
const form = useForm({ senha: '' });
const apagar = (senha: string) => {
    form.senha = senha;
    form.delete(rotas.contaResponsavel, {
        preserveScroll: true,
        onError: () => form.reset('senha'),
    });
};
</script>

<template>
    <section class="gb-cartao gb-form" aria-labelledby="resp-apagar">
        <h2 id="resp-apagar" class="gb-subtitulo">Apagar minha conta</h2>
        <Aviso v-if="bloqueadoPor.length > 0" tipo="info">
            Você ainda é o único responsável por
            <strong>{{ bloqueadoPor.join(', ') }}</strong
            >. Para apagar a sua conta, primeiro transfira a responsabilidade
            para outra pessoa ou apague a conta do aluno, em Meus alunos.
        </Aviso>
        <template v-else>
            <Aviso tipo="erro">
                Isso apaga a sua conta de responsável. Seus alunos continuam com
                os outros responsáveis. Não dá pra desfazer.
            </Aviso>
            <div v-if="!aberto" class="gb-acoes">
                <button
                    type="button"
                    class="l-btn l-btn--white"
                    @click="aberto = true"
                >
                    Quero apagar minha conta
                </button>
            </div>
            <ConfirmarAcao
                v-else
                titulo="Apagar a sua conta?"
                texto="Confirme com a sua senha."
                rotulo-confirmar="Apagar minha conta"
                pede-senha
                :erro-senha="form.errors.senha"
                :processando="form.processing"
                @confirmar="apagar"
                @cancelar="aberto = false"
            />
        </template>
    </section>
</template>
