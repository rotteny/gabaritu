import type { HumorFala } from '@/types/gabaritu';

type Tom = {
    f: number;
    f2?: number;
    t?: number;
    v?: number;
    type?: OscillatorType;
    at?: number;
    /** Fração da duração mantida no volume cheio antes de decair (padrão 0). */
    s?: number;
    /** Abaixa a música enquanto soa (padrão true); efeitos de interface não abaixam. */
    duck?: boolean;
};

/** Cadeia de saída: efeitos entram em `entrada`; a música entra em `musica` (com ducking). */
export type Palco = {
    ctx: BaseAudioContext;
    entrada: GainNode;
    musica: GainNode;
    duck: GainNode;
};

const GANHO_MASTER = 0.35;
const DUCK_NIVEL = 0.3;
const DUCK_SOLTAR = 0.7;
const entre = (a: number, b: number) => a + Math.random() * (b - a);

let ctx: AudioContext | null = null;
let palco: Palco | null = null;
let instalado = false;
const ouvintes = new Set<() => void>();

/** Monta a cadeia master (ganho + compressor) em qualquer contexto, ao vivo ou offline. */
export function montarPalco(c: BaseAudioContext): Palco {
    const compressor = c.createDynamicsCompressor();
    compressor.threshold.value = -18;
    compressor.knee.value = 20;
    compressor.ratio.value = 8;
    compressor.attack.value = 0.003;
    compressor.release.value = 0.2;
    const entrada = c.createGain();
    entrada.gain.value = GANHO_MASTER;
    entrada.connect(compressor).connect(c.destination);
    const duck = c.createGain();
    duck.connect(compressor);
    const musica = c.createGain();
    musica.connect(duck);

    return { ctx: c, entrada, musica, duck };
}

function criar(): AudioContext | null {
    if (ctx) {
        return ctx;
    }

    if (typeof window === 'undefined' || !('AudioContext' in window)) {
        return null;
    }

    try {
        ctx = new window.AudioContext();
        palco = montarPalco(ctx);
        ctx.addEventListener('statechange', () => ouvintes.forEach((f) => f()));
    } catch {
        ctx = null;
        palco = null;
    }

    return ctx;
}

/** Palco ao vivo, só quando o contexto já foi destravado. */
export function palcoAtivo(): Palco | null {
    return ctx?.state === 'running' ? palco : null;
}

/** Avisa quando o contexto muda de estado (ex.: destravou depois do primeiro gesto). */
export function aoMudarContexto(f: () => void) {
    ouvintes.add(f);

    return () => ouvintes.delete(f);
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

/** Abaixa a música enquanto o efeito soa e solta devagar depois. */
function abaixarMusica(p: Palco, t0: number, dur: number) {
    const g = p.duck.gain;
    g.cancelScheduledValues(t0);
    g.setTargetAtTime(DUCK_NIVEL, t0, 0.04);
    g.setTargetAtTime(1, t0 + dur, DUCK_SOLTAR / 3);
}

function tom(
    p: Palco | null,
    { f, f2, t = 0.1, v = 0.5, type = 'sine', at = 0, s = 0, duck = true }: Tom,
) {
    if (!p) {
        return;
    }

    const c = p.ctx;
    const t0 = c.currentTime + at;
    if (duck) {
        abaixarMusica(p, t0, t);
    }

    const osc = c.createOscillator();
    const ganho = c.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(f, t0);

    if (f2) {
        osc.frequency.exponentialRampToValueAtTime(f2, t0 + t);
    }

    ganho.gain.setValueAtTime(0.0001, t0);
    ganho.gain.exponentialRampToValueAtTime(v, t0 + 0.012);
    ganho.gain.setValueAtTime(v, t0 + 0.012 + t * s);
    ganho.gain.exponentialRampToValueAtTime(0.0001, t0 + t);
    osc.connect(ganho).connect(p.entrada);
    osc.start(t0);
    osc.stop(t0 + t + 0.03);
}

export function bipePeixoto(humor: HumorFala, p: Palco | null = palcoAtivo()) {
    const t = entre(0.09, 0.11);

    if (humor === 'bravo') {
        const f = entre(180, 300);
        tom(p, { f, f2: f * 0.7, type: 'sawtooth', v: 0.4, t });
    } else if (humor === 'feliz') {
        const f = entre(520, 820);
        tom(p, { f, f2: f * 1.5, v: 0.5, t });
    } else {
        const f = entre(250, 360);
        tom(p, { f, f2: f * 0.7, type: 'triangle', v: 0.6, t });
    }
}

/** Arpejo ascendente com brilho e um "pop" grave: a pequena explosão de acerto. */
export function somAcerto(p: Palco | null = palcoAtivo()) {
    [1, 1.26, 1.5, 2, 2.52].forEach((m, i) => {
        tom(p, { f: 440 * m, t: 0.2, type: 'triangle', v: 0.55, at: i * 0.06 });
        tom(p, { f: 880 * m, t: 0.12, type: 'sine', v: 0.2, at: i * 0.06 });
    });
    tom(p, { f: 180, f2: 60, t: 0.18, v: 0.7 });
}

/** Buzina cartoon "bu-bóu": dois degraus descendentes de onda quadrada (~400 ms), energia entre 400 e 1500 Hz para soar em caixa de notebook e celular. */
export function somErro(p: Palco | null = palcoAtivo()) {
    tom(p, { f: 620, f2: 560, t: 0.22, type: 'square', v: 0.24, s: 0.7 });
    tom(p, { f: 1240, f2: 1120, t: 0.22, type: 'sine', v: 0.14, s: 0.7 });
    tom(p, {
        f: 470,
        f2: 340,
        t: 0.22,
        type: 'square',
        v: 0.24,
        s: 0.6,
        at: 0.22,
    });
    tom(p, {
        f: 940,
        f2: 680,
        t: 0.22,
        type: 'sine',
        v: 0.14,
        s: 0.6,
        at: 0.22,
    });
}

/** "Plic" de bolhinha ao passar o mouse: ~40 ms, agudo, pitch aleatório, bem abaixo do clique. */
export function blipHover(p: Palco | null = palcoAtivo()) {
    const f = entre(1300, 2100);
    tom(p, { f, f2: f * 1.25, t: 0.04, v: 0.07, duck: false });
}

/** "Pop" suave quando uma seção da landing aparece pela primeira vez. */
export function popSecao(p: Palco | null = palcoAtivo()) {
    tom(p, { f: 340, f2: 700, t: 0.07, v: 0.1, duck: false });
}

/** "Tok" curto de clique em elemento selecionável. */
export function tokClique(p: Palco | null = palcoAtivo()) {
    const f = entre(480, 560);
    tom(p, { f, f2: f * 0.5, t: 0.06, v: 0.2, type: 'triangle', duck: false });
}
