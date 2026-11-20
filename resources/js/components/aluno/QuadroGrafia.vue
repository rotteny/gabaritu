<script setup lang="ts">
const palavras = ['gabarito', 'gabaritar', 'gabaritou'];
</script>

<template>
    <aside class="gb-quadro-grafia" aria-labelledby="qs-grafia">
        <h3 id="qs-grafia" class="gb-subtitulo">Na prova, escreva certo</h3>
        <ul class="gb-quadro-palavras">
            <li v-for="palavra in palavras" :key="palavra" class="gb-tag">
                {{ palavra }}
            </li>
        </ul>
        <p class="gb-texto">
            Gabaritu é só o nome do site. Na redação e nas outras provas, vale a
            grafia da língua.
        </p>
    </aside>
</template>
