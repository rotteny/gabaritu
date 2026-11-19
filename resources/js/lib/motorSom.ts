import type { HumorFala } from '@/types/gabaritu';

type Tom = {
    f: number;
    f2?: number;
    t?: number;
    v?: number;
    type?: OscillatorType;
    at?: number;
};

const GANHO_MASTER = 0.35;
const entre = (a: number, b: number) => a + Math.random() * (b - a);

let ctx: AudioContext | null = null;
let entrada: GainNode | null = null;
let instalado = false;

function criar(): AudioContext | null {
    if (ctx) {
        return ctx;
    }

    if (typeof window === 'undefined' || !('AudioContext' in window)) {
        return null;
    }

    try {
        ctx = new window.AudioContext();
        const compressor = ctx.createDynamicsCompressor();
        compressor.threshold.value = -18;
        compressor.knee.value = 20;
        compressor.ratio.value = 8;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.2;
        entrada = ctx.createGain();
        entrada.gain.value = GANHO_MASTER;
        entrada.connect(compressor).connect(ctx.destination);
    } catch {
        ctx = null;
    }

    return ctx;
}

/** Cria/retoma o contexto único. Deve rodar dentro de um gesto do usuário. */
export function destravarSom() {
    const c = criar();

    if (c?.state === 'suspended') {
        void c.resume();
    }
}

/** Escuta o primeiro gesto em qualquer lugar da página; sobrevive às visitas Inertia. */
export function instalarDestravamento() {
    if (instalado || typeof document === 'undefined') {
        return;
    }

    instalado = true;
    const eventos = ['pointerdown', 'keydown', 'touchstart'] as const;
    const aoGesto = () => {
        destravarSom();

        if (ctx?.state === 'running') {
            eventos.forEach((e) =>
                document.removeEventListener(e, aoGesto, true),
            );
        }
    };
    eventos.forEach((e) => document.addEventListener(e, aoGesto, true));
}

export function somDestravado(): boolean {
    return ctx?.state === 'running';
}

function tom({ f, f2, t = 0.1, v = 0.5, type = 'sine', at = 0 }: Tom) {
    if (!ctx || !entrada || ctx.state !== 'running') {
        return;
    }

    const t0 = ctx.currentTime + at;
    const osc = ctx.createOscillator();
    const ganho = ctx.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(f, t0);

    if (f2) {
        osc.frequency.exponentialRampToValueAtTime(f2, t0 + t);
    }

    ganho.gain.setValueAtTime(0.0001, t0);
    ganho.gain.exponentialRampToValueAtTime(v, t0 + 0.012);
    ganho.gain.exponentialRampToValueAtTime(0.0001, t0 + t);
    osc.connect(ganho).connect(entrada);
    osc.start(t0);
    osc.stop(t0 + t + 0.03);
}

export function bipePeixoto(humor: HumorFala) {
    const t = entre(0.09, 0.11);

    if (humor === 'bravo') {
        const f = entre(180, 300);
        tom({ f, f2: f * 0.7, type: 'sawtooth', v: 0.4, t });
    } else if (humor === 'feliz') {
        const f = entre(520, 820);
        tom({ f, f2: f * 1.5, v: 0.5, t });
    } else {
        const f = entre(250, 360);
        tom({ f, f2: f * 0.7, type: 'triangle', v: 0.6, t });
    }
}

/** Arpejo ascendente com brilho e um "pop" grave: a pequena explosão de acerto. */
export function somAcerto() {
    [1, 1.26, 1.5, 2, 2.52].forEach((m, i) => {
        tom({ f: 440 * m, t: 0.2, type: 'triangle', v: 0.55, at: i * 0.06 });
        tom({ f: 880 * m, t: 0.12, type: 'sine', v: 0.2, at: i * 0.06 });
    });
    tom({ f: 180, f2: 60, t: 0.18, v: 0.7 });
}

/** "Bóing" de buzina cartoon: square/sawtooth entre 300 e 1200 Hz, audível em caixa de notebook e celular. */
export function somErro() {
    tom({ f: 640, f2: 330, t: 0.28, type: 'square', v: 0.32 });
    tom({ f: 1280, f2: 660, t: 0.28, type: 'sine', v: 0.22 });
    tom({ f: 420, f2: 900, t: 0.2, type: 'sawtooth', v: 0.3, at: 0.26 });
    tom({ f: 840, f2: 1200, t: 0.2, type: 'sine', v: 0.2, at: 0.26 });
}
