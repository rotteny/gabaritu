import { onBeforeUnmount, onMounted } from 'vue';

const CLASSE = 'gb-tela-fixa';

/** Trava a página na altura da janela (sem scroll vertical) enquanto a tela estiver montada. */
export function useTelaFixa() {
    onMounted(() => document.documentElement.classList.add(CLASSE));
    onBeforeUnmount(() => document.documentElement.classList.remove(CLASSE));
}
