<script setup lang="ts">
import { useId, useTemplateRef, watch } from 'vue';

const aberto = defineModel<boolean>({ default: false });
defineProps<{ titulo: string; classe?: string }>();

const dlg = useTemplateRef<HTMLDialogElement>('dlg');
const idTitulo = useId();

watch(aberto, (valor) => {
    const el = dlg.value;

    if (!el) {
        return;
    }

    if (valor && !el.open) {
        el.showModal();
    } else if (!valor && el.open) {
        el.close();
    }
});

function aoClicar(e: MouseEvent) {
    if (e.target === dlg.value) {
        aberto.value = false;
    }
}
</script>

<template>
    <dialog
        ref="dlg"
        :class="classe"
        :aria-labelledby="idTitulo"
        @close="aberto = false"
        @click="aoClicar"
    >
        <div v-if="aberto" class="dlg-corpo">
            <h2 :id="idTitulo" class="dlg-titulo">{{ titulo }}</h2>
            <slot :fechar="() => (aberto = false)" />
        </div>
    </dialog>
</template>
