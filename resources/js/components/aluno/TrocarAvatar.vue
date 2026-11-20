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
import { useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { ref } from 'vue';
import AvatarAluno from '@/components/aluno/AvatarAluno.vue';
import EscolhaAvatar from '@/components/aluno/EscolhaAvatar.vue';
import { rotas } from '@/lib/rotas';

const props = defineProps<{ atual: string }>();

const aberto = ref(false);
const form = useForm({ avatar: props.atual });

const aoAbrir = (valor: boolean) => {
    aberto.value = valor;
    if (valor) {
        form.reset();
        form.clearErrors();
        form.avatar = props.atual;
    }
};
const salvar = () =>
    form.patch(rotas.contaPerfil, {
        preserveScroll: true,
        preserveState: true,
    });
</script>

<template>
    <DialogRoot :open="aberto" @update:open="aoAbrir">
        <DialogTrigger
            class="gb-nav-avatar"
            type="button"
            aria-label="Trocar avatar"
        >
            <AvatarAluno :slug="atual" :tamanho="32" decorativo />
        </DialogTrigger>
        <DialogPortal to=".gb">
            <DialogOverlay class="gb-dialogo-fundo" />
            <DialogContent class="gb-dialogo gb-cartao">
                <DialogClose class="gb-dialogo-fechar" aria-label="Fechar">
                    <X :size="22" aria-hidden="true" />
                </DialogClose>
                <DialogTitle class="gb-subtitulo"
                    >Escolha seu avatar</DialogTitle
                >
                <DialogDescription class="gb-miudo">
                    Ele aparece no topo das suas telas e para o seu responsável.
                </DialogDescription>
                <form class="gb-form" novalidate @submit.prevent="salvar">
                    <EscolhaAvatar
                        v-model="form.avatar"
                        legenda="Avatares"
                        :erro="form.errors.avatar"
                    />
                    <p
                        v-if="form.hasErrors && !form.errors.avatar"
                        class="gb-aviso gb-aviso--erro"
                        role="alert"
                    >
                        Não deu para trocar o avatar. Tente de novo.
                    </p>
                    <p
                        v-if="form.recentlySuccessful"
                        class="gb-aviso gb-aviso--ok"
                        role="status"
                    >
                        Avatar trocado.
                    </p>
                    <div class="gb-acoes">
                        <button
                            type="submit"
                            class="l-btn l-btn--orange"
                            :disabled="form.processing || form.avatar === atual"
                        >
                            Salvar o avatar
                        </button>
                        <DialogClose class="l-btn l-btn--white">
                            Fechar
                        </DialogClose>
                    </div>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
