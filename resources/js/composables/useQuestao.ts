import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { Ref } from 'vue';
import { usePodeFalar } from '@/composables/usePodeFalar';
import { useFalaDigitada } from '@/composables/useFalaDigitada';
import { useEsperaResposta } from '@/composables/useEsperaResposta';
import { useMovimentoReduzido } from '@/composables/useMovimentoReduzido';
import { useControleSom } from '@/composables/useMusica';
import { humorDaFala } from '@/lib/grito';
import { useSomPeixoto } from '@/composables/useSomPeixoto';
import { ErroHttp } from '@/lib/http';
import { enviarResposta } from '@/lib/respostas';
import { rotas } from '@/lib/rotas';
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
    const espera = useEsperaResposta();

    const fase = ref<FaseQuestao>('perguntando');
    const escolhida = ref<Alternativa | null>(null);
    const resposta = ref<RespostaApi | null>(null);
    const erroEnvio = ref<string | null>(null);
    const falaAtual = ref<Fala>(abertura.value);
    const efeitosPulados = ref(false);
    let montado = true;
    const cancelador = new AbortController();

    // Enquanto a resposta não chega o humor é neutro: a fala de abertura não decide reação.
    const humor = computed(() =>
        espera.visivel.value
            ? 'neutro'
            : humorDaFala(falaAtual.value.texto, falaAtual.value.humor),
    );
    const acertou = computed(() => resposta.value?.correta === true);
    const freio = computed(() => resposta.value?.freio_ativo === true);
    const ultima = computed(
        () => tentativa.value.posicao >= tentativa.value.total,
    );

    void fala.falar(abertura.value.texto, abertura.value.humor);

    /** 422 = a tela está defasada (ex.: já respondida): pede o estado real ao servidor. */
    function recarregarEstado() {
        router.visit(rotas.tentativa(tentativa.value.id), { replace: true });
    }

    async function responder(alternativa: Alternativa) {
        if (fase.value !== 'perguntando') {
            return;
        }

        som.destravar();
        erroEnvio.value = null;
        escolhida.value = alternativa;
        fase.value = 'enviando';
        fala.pular();
        espera.iniciar();

        let recebida: RespostaApi;

        try {
            recebida = await enviarResposta(
                tentativa.value.id,
                {
                    questao_id: questao.value.id,
                    alternativa_id: alternativa.id,
                },
                cancelador.signal,
            );
            await espera.encerrar();
        } catch (e) {
            espera.cancelar();

            if (!montado) {
                return;
            }

            if (e instanceof ErroHttp && e.status === 422) {
                recarregarEstado();

                return;
            }

            // Mantém a escolha marcada: "tentar de novo" reenvia a mesma alternativa.
            fase.value = 'perguntando';
            erroEnvio.value =
                e instanceof Error ? e.message : 'Não deu pra enviar.';

            return;
        }

        if (!montado) {
            return;
        }

        resposta.value = recebida;
        fase.value = 'reagindo';
        falaAtual.value = recebida.fala;

        if (recebida.correta) {
            som.acerto();
        } else if (!recebida.freio_ativo) {
            som.erro();
        }

        const terminou = await fala.falar(
            recebida.fala.texto,
            recebida.fala.humor,
        );

        if (terminou && montado && fase.value === 'reagindo') {
            fase.value = 'explicada';
        }
    }

    function tentarDeNovo() {
        if (escolhida.value) {
            void responder(escolhida.value);
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
        cancelador.abort();
        fala.pular();
    });

    return {
        fase,
        escolhida,
        resposta,
        erroEnvio,
        esperando: espera.visivel,
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
        tentarDeNovo,
        pularAnimacao,
        proxima,
    };
}
