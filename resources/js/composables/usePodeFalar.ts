import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import { aoMudarContexto, somDestravado } from '@/lib/motorSom';

/**
 * O Peixoto só fala quando está sendo visto: elemento ao menos 50% na viewport,
 * aba visível e áudio já destravado pelo primeiro gesto.
 */
export function usePodeFalar(alvo: () => Element | null): Ref<boolean> {
    const naTela = ref(false);
    const abaVisivel = ref(true);
    const audioOk = ref(somDestravado());
    const pode = ref(false);
    let io: IntersectionObserver | null = null;
    let soltar = () => {};

    const recalcular = () => {
        pode.value = naTela.value && abaVisivel.value && audioOk.value;
    };
    const aoAba = () => {
        abaVisivel.value = !document.hidden;
        recalcular();
    };

    onMounted(() => {
        const el = alvo();

        if (el) {
            io = new IntersectionObserver(
                ([e]) => {
                    naTela.value = e.isIntersecting;
                    recalcular();
                },
                { threshold: 0.5 },
            );
            io.observe(el);
        }

        aoAba();
        document.addEventListener('visibilitychange', aoAba);
        soltar = aoMudarContexto(() => {
            audioOk.value = somDestravado();
            recalcular();
        });
        audioOk.value = somDestravado();
        recalcular();
    });
    onBeforeUnmount(() => {
        io?.disconnect();
        document.removeEventListener('visibilitychange', aoAba);
        soltar();
    });

    return pode;
}
