<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ emailContato: string }>();
</script>

<template>
    <Head title="Gabaritu: em breve" />
    <main
        class="flex h-dvh flex-col items-center justify-center gap-5 overflow-hidden bg-[#8fd3f4] px-6 text-center text-black"
    >
        <svg
            viewBox="0 0 200 160"
            class="h-40 w-48 shrink-0"
            role="img"
            aria-label="Peixoto, o baiacu"
        >
            <path
                d="M20 80 L-2 55 L-2 105 Z"
                transform="translate(6 0)"
                fill="#ffb703"
                stroke="#000"
                stroke-width="6"
                stroke-linejoin="round"
            />
            <ellipse cx="108" cy="88" rx="82" ry="62" fill="#000" />
            <ellipse
                cx="100"
                cy="80"
                rx="82"
                ry="62"
                fill="#ffd166"
                stroke="#000"
                stroke-width="6"
            />
            <path
                d="M52 56 L40 36 M80 40 L76 18 M112 38 L118 16 M142 48 L158 30"
                stroke="#000"
                stroke-width="6"
                stroke-linecap="round"
            />
            <circle
                cx="128"
                cy="68"
                r="17"
                fill="#fff"
                stroke="#000"
                stroke-width="6"
            />
            <circle cx="132" cy="70" r="6" fill="#000" />
            <path
                d="M118 52 L142 58"
                stroke="#000"
                stroke-width="6"
                stroke-linecap="round"
            />
            <path
                d="M138 106 Q158 96 172 106"
                fill="none"
                stroke="#000"
                stroke-width="6"
                stroke-linecap="round"
            />
        </svg>

        <h1 class="text-4xl font-black sm:text-5xl">Gabaritu</h1>

        <p
            class="max-w-md rounded-xl border-4 border-black bg-white px-5 py-3 text-lg font-bold shadow-[6px_6px_0_#000]"
        >
            Peixoto: "Cheguei antes da prova. Você também deveria, mas calma: eu
            ainda estou inflando."
        </p>

        <p class="max-w-md text-lg font-semibold">
            O Gabaritu ainda não abriu. Quer saber quando abrir ou ajudar?
            Escreva para
            <a
                :href="`mailto:${emailContato}`"
                class="underline decoration-4 underline-offset-4"
                >{{ emailContato }}</a
            >.
        </p>
    </main>
</template>
