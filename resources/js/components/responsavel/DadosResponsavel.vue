<script setup lang="ts">
defineProps<{ nome: string; email: string; anoNascimento?: number | null }>();
</script>

<template>
    <section class="gb-cartao gb-prosa" aria-labelledby="resp-dados">
        <h2 id="resp-dados" class="gb-subtitulo">Seus dados</h2>
        <dl class="gb-definicoes">
            <dt>Nome</dt>
            <dd>{{ nome }}</dd>
            <dt>E-mail</dt>
            <dd>{{ email }}</dd>
            <template v-if="anoNascimento">
                <dt>Ano de nascimento</dt>
                <dd>{{ anoNascimento }}</dd>
            </template>
        </dl>
        <p class="gb-miudo">
            Só guardamos o necessário para identificar e responsabilizar você.
            Para mudar nome ou e-mail, escreva para o contato na página de
            privacidade.
        </p>
    </section>
</template>
