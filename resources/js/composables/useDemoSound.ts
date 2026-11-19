import { ref } from 'vue';

type Efeito = 'acerto' | 'erro';

/**
 * Efeitos sonoros da demo. O AudioContext só nasce dentro de um clique do
 * usuário (política dos navegadores e regra de acessibilidade da página).
 */
export function useDemoSound() {
    const ligado = ref(true);
    let contexto: AudioContext | null = null;

    function obterContexto(): AudioContext | null {
        if (
            !contexto &&
            typeof window !== 'undefined' &&
            'AudioContext' in window
        ) {
            contexto = new AudioContext();
        }

        return contexto;
    }

    function nota(
        ctx: AudioContext,
        freq: number,
        inicio: number,
        duracao: number,
        tipo: OscillatorType,
    ) {
        const osc = ctx.createOscillator();
        const ganho = ctx.createGain();
        osc.type = tipo;
        osc.frequency.setValueAtTime(freq, inicio);
        ganho.gain.setValueAtTime(0.0001, inicio);
        ganho.gain.exponentialRampToValueAtTime(0.18, inicio + 0.02);
        ganho.gain.exponentialRampToValueAtTime(0.0001, inicio + duracao);
        osc.connect(ganho).connect(ctx.destination);
        osc.start(inicio);
        osc.stop(inicio + duracao + 0.05);
    }

    function tocar(efeito: Efeito) {
        const ctx = ligado.value ? obterContexto() : null;

        if (!ctx) {
            return;
        }

        void ctx.resume();
        const t = ctx.currentTime;

        if (efeito === 'acerto') {
            [523, 659, 784, 1047].forEach((f, i) =>
                nota(ctx, f, t + i * 0.09, 0.22, 'triangle'),
            );

            return;
        }

        [330, 277, 233, 175].forEach((f, i) =>
            nota(ctx, f, t + i * 0.14, 0.26, 'sawtooth'),
        );
    }

    function alternar() {
        ligado.value = !ligado.value;
    }

    return { ligado, tocar, alternar };
}
