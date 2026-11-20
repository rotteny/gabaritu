import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useAluno } from '@/composables/useAluno';
import { definirMusicaAtiva, reterMusica } from '@/lib/motorMusica';
import { destravarSom } from '@/lib/motorSom';
import { rotas } from '@/lib/rotas';
import type { PropsCompartilhadas } from '@/types/gabaritu';

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

const CHAVE_LOCAL = 'gabaritu:som';

function lerLocal(): { som: boolean; musica: boolean } | null {
    try {
        const bruto = window.localStorage.getItem(CHAVE_LOCAL);

        return bruto ? JSON.parse(bruto) : null;
    } catch {
        return null;
    }
}

function gravarLocal() {
    try {
        window.localStorage.setItem(
            CHAVE_LOCAL,
            JSON.stringify({
                som: somLigado.value,
                musica: musicaLigada.value,
            }),
        );
    } catch {
        // Navegador sem armazenamento: a preferência vale só nesta visita.
    }
}

/** Raiz do app: música e som valem em qualquer página Inertia, inclusive a landing. Chamar uma vez. */
export function iniciarSomGlobal() {
    const local = lerLocal();

    if (local) {
        somLigado.value = local.som !== false;
        musicaLigada.value = local.musica !== false;
    }

    ligarAoMotor();
    reterMusica();
    router.on('navigate', (evento) => {
        const usuario = (
            evento.detail.page.props as unknown as Partial<PropsCompartilhadas>
        ).auth?.user;
        const prefs =
            usuario && usuario.tipo !== 'responsavel'
                ? usuario.preferencias
                : undefined;

        if (prefs) {
            somLigado.value = prefs.som;
            musicaLigada.value = prefs.musica;
        }
    });
}

/** Botão único de som + música: logado salva no servidor; deslogado, no localStorage. */
export function useAlternarSom() {
    const { preferencias } = useAluno();
    const ligado = computed(() => somLigado.value && musicaLigada.value);

    function alternar() {
        const novo = !ligado.value;
        somLigado.value = novo;
        musicaLigada.value = novo;

        if (novo) {
            destravarSom();
        }

        if (preferencias.value) {
            router.patch(
                rotas.conta + '/preferencias',
                { som: novo, musica: novo },
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['auth', 'errors'],
                },
            );
        } else {
            gravarLocal();
        }
    }

    return { ligado, alternar };
}
