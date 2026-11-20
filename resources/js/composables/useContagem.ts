import { onBeforeUnmount, ref } from 'vue';

/** Contagem regressiva em segundos, usada para travar o botão de reenvio. */
export function useContagem() {
    const restante = ref(0);
    let timer: ReturnType<typeof setInterval> | null = null;

    const parar = () => {
        if (timer) clearInterval(timer);
        timer = null;
    };

    const iniciar = (segundos: number) => {
        parar();
        restante.value = segundos;
        timer = setInterval(() => {
            restante.value -= 1;
            if (restante.value <= 0) parar();
        }, 1000);
    };

    onBeforeUnmount(parar);
    return { restante, iniciar };
}
