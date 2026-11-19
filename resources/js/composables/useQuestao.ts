import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { Ref } from 'vue';
import { usePodeFalar } from '@/composables/usePodeFalar';
import { useFalaDigitada } from '@/composables/useFalaDigitada';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { useControleSom } from '@/composables/useMusica';
import { humorDaFala } from '@/lib/grito';
import { useSomPeixoto } from '@/composables/useSomPeixoto';
import { enviarResposta } from '@/lib/respostas';
import type {
    Alternativa,
    Fala,
    QuestaoDados,
    RespostaApi,
    Tentativa,
} from '@/types/gabaritu';

export type FaseQuestao = 'perguntando' | 'enviando' | 'reagindo' | 'explicada';

type Entrada = {
    tentativa: Readonly<Ref<Tentativa>>;
    questao: Readonly<Ref<QuestaoDados>>;
    abertura: Readonly<Ref<Fala>>;
};

/** Máquina de estados da tela de questão: perguntar, enviar, reagir (piada) e explicar. */
export function useQuestao({ tentativa, questao, abertura }: Entrada) {
    const reduzido = useMovimentoReduzido();
    const { somLigado: somLocal } = useControleSom();
    const som = useSomPeixoto(somLocal);
    const podeFalar = usePodeFalar(() => document.querySelector('.q-palco'));
    const fala = useFalaDigitada(reduzido, som.bipe, podeFalar);

    const fase = ref<FaseQuestao>('perguntando');
    const escolhida = ref<Alternativa | null>(null);
    const resposta = ref<RespostaApi | null>(null);
    const erroEnvio = ref<string | null>(null);
    const falaAtual = ref<Fala>(abertura.value);
    const efeitosPulados = ref(false);
    let montado = true;

    const humor = computed(() =>
        humorDaFala(falaAtual.value.texto, falaAtual.value.humor),
    );
    const acertou = computed(() => resposta.value?.correta === true);
    const freio = computed(() => resposta.value?.freio_ativo === true);
    const ultima = computed(
        () => tentativa.value.posicao >= tentativa.value.total,
    );

    void fala.falar(abertura.value.texto, abertura.value.humor);

    async function responder(alternativa: Alternativa) {
        if (fase.value !== 'perguntando') {
            return;
        }

        som.destravar();
        erroEnvio.value = null;
        escolhida.value = alternativa;
        fase.value = 'enviando';

        try {
            resposta.value = await enviarResposta(tentativa.value.id, {
                questao_id: questao.value.id,
                alternativa_id: alternativa.id,
            });
        } catch (e) {
            escolhida.value = null;
            fase.value = 'perguntando';
            erroEnvio.value =
                e instanceof Error ? e.message : 'Não deu pra enviar.';

            return;
        }

        fase.value = 'reagindo';
        falaAtual.value = resposta.value.fala;

        if (resposta.value.correta) {
            som.acerto();
        } else if (!resposta.value.freio_ativo) {
            som.erro();
        }

        const terminou = await fala.falar(
            resposta.value.fala.texto,
            resposta.value.fala.humor,
        );

        if (terminou && montado && fase.value === 'reagindo') {
            fase.value = 'explicada';
        }
    }

    function pularAnimacao() {
        if (fase.value !== 'reagindo') {
            return;
        }

        efeitosPulados.value = true;
        fala.pular();
    }

    function proxima() {
        const destino = resposta.value?.proxima;

        if (!destino) {
            return;
        }

        router.visit('url' in destino ? destino.url : destino.resultado_url);
    }

    onBeforeUnmount(() => {
        montado = false;
        fala.pular();
    });

    return {
        fase,
        escolhida,
        resposta,
        erroEnvio,
        humor,
        acertou,
        freio,
        ultima,
        efeitosPulados,
        reduzido,
        digitado: fala.digitado,
        falando: fala.falando,
        textoCompleto: computed(() => falaAtual.value.texto),
        responder,
        pularAnimacao,
        proxima,
    };
}
