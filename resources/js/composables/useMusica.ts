import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { useAluno } from '@/composables/useAluno';
import { definirMusicaAtiva, reterMusica } from '@/lib/motorMusica';
import { destravarSom } from '@/lib/motorSom';
import { rotas } from '@/lib/rotas';

/** Estado único (som de efeitos e música) compartilhado entre layouts, questão e landing. */
const somLigado = ref(true);
const musicaLigada = ref(true);
/** Leitura do estado de som sem efeitos colaterais (não liga a música ao motor). */
export const somLigadoRef = somLigado;
let ligadoAoMotor = false;

function ligarAoMotor() {
    if (ligadoAoMotor) {
        return;
    }

    ligadoAoMotor = true;
    watch(
        [somLigado, musicaLigada],
        ([som, musica]) => definirMusicaAtiva(som && musica),
        { immediate: true },
    );
}

/** Estado compartilhado de som e música; a música só toca com o som ligado. */
export function useControleSom() {
    ligarAoMotor();

    return { somLigado, musicaLigada };
}

/** Alterna a música; no app logado salva a preferência `musica` no servidor. */
export function useAlternarMusica() {
    const { preferencias } = useAluno();

    function alternarMusica() {
        musicaLigada.value = !musicaLigada.value;

        if (musicaLigada.value) {
            destravarSom();
        }

        if (preferencias.value) {
            router.patch(
                rotas.conta + '/preferencias',
                { musica: musicaLigada.value },
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['auth', 'errors'],
                },
            );
        }
    }

    return { musicaLigada, alternarMusica };
}

/**
 * Telas que querem música de fundo (layouts do app e demo da landing).
 * Sincroniza com as preferências do aluno, quando existem, e segura a música enquanto montadas.
 */
export function useMusicaAmbiente() {
    const { preferencias } = useAluno();
    useControleSom();

    watch(
        () => [preferencias.value?.som, preferencias.value?.musica] as const,
        ([som, musica]) => {
            if (som !== undefined) {
                somLigado.value = som;
            }

            if (musica !== undefined) {
                musicaLigada.value = musica;
            }
        },
        { immediate: true },
    );

    const soltar = reterMusica();
    onBeforeUnmount(soltar);
}
