<script setup lang="ts">
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { X } from '@lucide/vue';
import type { ImagemQuestao } from '@/types/gabaritu';

/** Botão (slot) que abre o zoom da imagem num diálogo com o alt como legenda. Esc fecha e o foco volta ao botão. */
defineOptions({ inheritAttrs: false });
defineProps<{ imagem: ImagemQuestao; rotulo?: string }>();
</script>

<template>
    <DialogRoot>
        <DialogTrigger
            v-bind="$attrs"
            type="button"
            :aria-label="rotulo"
            :aria-haspopup="'dialog'"
        >
            <slot />
        </DialogTrigger>
        <DialogPortal to=".gb">
            <DialogOverlay class="gb-dialogo-fundo" />
            <DialogContent class="gb-dialogo gb-cartao q-zoom">
                <DialogClose class="gb-dialogo-fechar" aria-label="Fechar">
                    <X :size="22" aria-hidden="true" />
                </DialogClose>
                <DialogTitle class="q-zoom-legenda">{{
                    imagem.alt
                }}</DialogTitle>
                <DialogDescription class="gb-sr">
                    Imagem ampliada. Esc fecha.
                </DialogDescription>
                <img
                    class="q-zoom-img"
                    :src="imagem.url"
                    alt=""
                    :width="imagem.largura"
                    :height="imagem.altura"
                />
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
