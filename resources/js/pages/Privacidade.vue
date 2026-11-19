<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import LinkExterno from '@/components/LinkExterno.vue';
import FalaPeixoto from '@/components/aluno/FalaPeixoto.vue';
import { useAluno } from '@/composables/useAluno';
import { rotas } from '@/lib/rotas';
import type { Fala } from '@/types/gabaritu';

defineProps<{ fala?: Fala | null }>();

const { gabaritu, aluno } = useAluno();

const coletamos = [
    [
        'Apelido',
        'o nome que aparece pra você. Não precisa ser o seu nome de verdade.',
    ],
    [
        'Ano de nascimento',
        'pra checar a idade mínima (13 anos) e liberar o nível de zoeira certo.',
    ],
    ['Série', 'pra organizar o seu estudo.'],
    ['E-mail', 'pra você entrar e recuperar a senha.'],
    [
        'Suas respostas',
        'o que você marcou, se acertou e quanto tempo levou pra responder. É daí que saem a nota estimada e a revisão.',
    ],
    [
        'Suas preferências',
        'nível de zoeira, som, reduzir movimento e a última matéria que você abriu.',
    ],
];
</script>

<template>
    <Head title="Privacidade" />
    <h1 class="gb-titulo">Privacidade, sem enrolação</h1>
    <section class="gb-cartao gb-prosa" aria-labelledby="p-resumo">
        <h2 id="p-resumo" class="gb-subtitulo">O resumo</h2>
        <ul class="gb-lista-simples">
            <li>O Gabaritu guarda só o que precisa pra funcionar.</li>
            <li>Ninguém além de você vê o seu desempenho.</li>
            <li>Você apaga a conta e tudo some, quando quiser.</li>
        </ul>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-coleta">
        <h2 id="p-coleta" class="gb-subtitulo">O que a gente guarda</h2>
        <dl class="gb-definicoes">
            <template v-for="[titulo, texto] in coletamos" :key="titulo">
                <dt>{{ titulo }}</dt>
                <dd>{{ texto }}</dd>
            </template>
        </dl>
        <p class="gb-texto">
            A sua senha é guardada embaralhada (criptografada): nem a gente
            consegue ler.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-nao">
        <h2 id="p-nao" class="gb-subtitulo">O que a gente não guarda</h2>
        <p class="gb-texto">
            Nome completo, CPF, telefone, endereço, localização e fotos. O
            Gabaritu também não tem anúncio e não vende dado de ninguém.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-quem">
        <h2 id="p-quem" class="gb-subtitulo">Quem vê o seu desempenho</h2>
        <p class="gb-texto">
            Só você. Outros alunos, professores e responsáveis não têm acesso às
            suas notas e respostas. O código do Gabaritu é aberto: qualquer
            pessoa pode conferir o que ele faz
            <LinkExterno
                v-if="gabaritu"
                :href="gabaritu.repositorio_url"
                class="gb-link"
                >no repositório</LinkExterno
            >.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-cookies">
        <h2 id="p-cookies" class="gb-subtitulo">Cookies</h2>
        <p class="gb-texto">
            Usamos só os cookies necessários pra você continuar logado e pra
            proteger o site. Nada de cookie de anúncio.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-idade">
        <h2 id="p-idade" class="gb-subtitulo">Idade mínima</h2>
        <p class="gb-texto">
            O Gabaritu é pra quem tem 13 anos ou mais. O nível de zoeira "sem
            filtro" só é liberado a partir de 15 anos.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-apagar">
        <h2 id="p-apagar" class="gb-subtitulo">Apagar tudo</h2>
        <p class="gb-texto">
            Na página
            <Link v-if="aluno" :href="rotas.conta" class="gb-link">Conta</Link
            ><template v-else>Conta</template>, em "Apagar minha conta", você
            apaga o seu cadastro e tudo que fez no Gabaritu. Não dá pra
            desfazer.
        </p>
    </section>

    <section class="gb-cartao gb-prosa" aria-labelledby="p-contato">
        <h2 id="p-contato" class="gb-subtitulo">Ficou com dúvida?</h2>
        <p class="gb-texto">
            Escreva para
            <a
                v-if="gabaritu"
                :href="`mailto:${gabaritu.email_contato}`"
                class="gb-link"
                >{{ gabaritu.email_contato }}</a
            >.
        </p>
    </section>

    <FalaPeixoto v-if="fala" :fala="fala" />
</template>
