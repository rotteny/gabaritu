import { aoMudarContexto, palcoAtivo } from '@/lib/motorSom';
import type { Palco } from '@/lib/motorSom';

/** Música de fundo procedural: marimba, pad suave, baixo e bolhinhas em dó maior pentatônico. */

const BPM = 84;
const COLCHEIA = 60 / BPM / 2;
const COMPASSO = COLCHEIA * 8;
const SWING = 0.1;
const NIVEL = 0.2;
const FADE_ENTRADA = 2;
const ANTECEDENCIA = 0.5;

const hz = (midi: number) => 440 * 2 ** ((midi - 69) / 12);
const sorteia = <T>(lista: readonly T[]): T =>
    lista[Math.floor(Math.random() * lista.length)];
const entre = (a: number, b: number) => a + Math.random() * (b - a);

type Acorde = { baixo: number; notas: readonly number[] };
const C7M: Acorde = { baixo: 48, notas: [60, 64, 67, 71] };
const AM7: Acorde = { baixo: 45, notas: [57, 60, 64, 67] };
const F7M: Acorde = { baixo: 41, notas: [53, 57, 60, 64] };
const G6: Acorde = { baixo: 43, notas: [55, 59, 62, 64] };
const EM7: Acorde = { baixo: 40, notas: [52, 55, 59, 62] };
const DM7: Acorde = { baixo: 38, notas: [50, 53, 57, 60] };

const PROGRESSOES: readonly (readonly Acorde[])[] = [
    [C7M, AM7, F7M, G6],
    [F7M, EM7, DM7, G6],
    [C7M, EM7, F7M, G6],
    [AM7, F7M, C7M, G6],
];
const PENTATONICA = [67, 69, 72, 74, 76, 79, 81, 84] as const;

export type Saida = { bus: GainNode; eco: GainNode };

/** Cria a saída da música (volume + eco) ligada em `destino`. */
export function montarSaida(c: BaseAudioContext, destino: AudioNode): Saida {
    const bus = c.createGain();
    bus.connect(destino);
    const eco = c.createGain();
    eco.gain.value = 0.3;
    const atraso = c.createDelay(1);
    atraso.delayTime.value = COLCHEIA * 3;
    const realimenta = c.createGain();
    realimenta.gain.value = 0.3;
    const filtro = c.createBiquadFilter();
    filtro.type = 'lowpass';
    filtro.frequency.value = 2200;
    eco.connect(atraso).connect(filtro).connect(bus);
    filtro.connect(realimenta).connect(atraso);

    return { bus, eco };
}

function nota(
    c: BaseAudioContext,
    s: Saida,
    {
        f,
        t0,
        dur,
        v,
        parcial = 0,
        tipo = 'sine',
        ataque = 0.004,
    }: {
        f: number;
        t0: number;
        dur: number;
        v: number;
        parcial?: number;
        tipo?: OscillatorType;
        ataque?: number;
    },
    destino: AudioNode = s.bus,
) {
    const osc = c.createOscillator();
    const g = c.createGain();
    osc.type = tipo;
    osc.frequency.value = f;
    g.gain.setValueAtTime(0.0001, t0);
    g.gain.exponentialRampToValueAtTime(v, t0 + ataque);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    osc.connect(g).connect(destino);
    osc.start(t0);
    osc.stop(t0 + dur + 0.05);

    if (parcial) {
        nota(
            c,
            s,
            { f: f * parcial, t0, dur: dur * 0.18, v: v * 0.35 },
            destino,
        );
    }
}

function marimba(
    c: BaseAudioContext,
    s: Saida,
    midi: number,
    t0: number,
    v: number,
) {
    nota(c, s, { f: hz(midi), t0, dur: 0.9, v, parcial: 3.93 });
    nota(c, s, { f: hz(midi), t0, dur: 0.9, v: v * 0.5 }, s.eco);
}

function pad(c: BaseAudioContext, s: Saida, acorde: Acorde, t0: number) {
    const filtro = c.createBiquadFilter();
    filtro.type = 'lowpass';
    filtro.frequency.value = 800;
    filtro.connect(s.bus);
    acorde.notas.forEach((m) => {
        const osc = c.createOscillator();
        const g = c.createGain();
        osc.type = 'triangle';
        osc.frequency.value = hz(m - 12);
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(0.05, t0 + 0.9);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + COMPASSO + 0.7);
        osc.connect(g).connect(filtro);
        osc.start(t0);
        osc.stop(t0 + COMPASSO + 0.8);
    });
}

function bolha(c: BaseAudioContext, s: Saida, t0: number) {
    const osc = c.createOscillator();
    const g = c.createGain();
    const f = entre(520, 900);
    osc.frequency.setValueAtTime(f, t0);
    osc.frequency.exponentialRampToValueAtTime(f * 2.3, t0 + 0.09);
    g.gain.setValueAtTime(0.0001, t0);
    g.gain.exponentialRampToValueAtTime(0.07, t0 + 0.015);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.11);
    osc.connect(g).connect(s.bus);
    osc.connect(g).connect(s.eco);
    osc.start(t0);
    osc.stop(t0 + 0.15);
}

/** Estado de composição: qual progressão, qual compasso e o motivo da melodia. */
export type Gerador = {
    progressao: readonly Acorde[];
    compasso: number;
    passo: number;
    motivo: (number | null)[];
};

export function novoGerador(): Gerador {
    return { progressao: PROGRESSOES[0], compasso: 0, passo: 3, motivo: [] };
}

function criarMotivo(g: Gerador): (number | null)[] {
    if (g.motivo.length && Math.random() < 0.4) {
        return g.motivo.map((n) =>
            n !== null && Math.random() < 0.2
                ? Math.min(7, Math.max(0, n + sorteia([-1, 1])))
                : n,
        );
    }

    return Array.from({ length: 8 }, (_, i) => {
        if (Math.random() > (i % 4 === 0 ? 0.85 : 0.4)) {
            return null;
        }
        g.passo = Math.min(
            7,
            Math.max(0, g.passo + sorteia([-2, -1, -1, 0, 1, 1, 2])),
        );

        return g.passo;
    });
}

/** Agenda um compasso inteiro (8 colcheias) começando em `t0`. */
export function agendarCompasso(
    c: BaseAudioContext,
    s: Saida,
    g: Gerador,
    t0: number,
) {
    if (g.compasso % 4 === 0 && g.compasso > 0) {
        g.progressao = sorteia(PROGRESSOES);
    }

    const acorde = g.progressao[g.compasso % 4];
    pad(c, s, acorde, t0);
    nota(c, s, { f: hz(acorde.baixo), t0, dur: 0.8, v: 0.3 });

    if (Math.random() < 0.6) {
        nota(c, s, {
            f: hz(acorde.baixo),
            t0: t0 + COLCHEIA * 4,
            dur: 0.6,
            v: 0.2,
        });
    }

    g.motivo = criarMotivo(g);
    g.motivo.forEach((n, i) => {
        if (n !== null) {
            const balanco = i % 2 ? COLCHEIA * SWING : 0;
            marimba(
                c,
                s,
                PENTATONICA[n],
                t0 + i * COLCHEIA + balanco,
                i % 4 === 0 ? 0.22 : 0.15,
            );
        }
    });

    if (Math.random() < 0.3) {
        bolha(c, s, t0 + entre(0.2, COMPASSO - 0.3));
    }

    g.compasso += 1;
}

type Tocando = {
    palco: Palco;
    saida: Saida;
    gerador: Gerador;
    timer: number;
    proximo: number;
};

let ativa = false;
let retidos = 0;
let atual: Tocando | null = null;
let instalado = false;

function iniciar(palco: Palco) {
    const c = palco.ctx;
    const saida = montarSaida(c, palco.musica);
    const agora = c.currentTime;
    saida.bus.gain.setValueAtTime(0, agora);
    saida.bus.gain.linearRampToValueAtTime(NIVEL, agora + FADE_ENTRADA);
    const t: Tocando = {
        palco,
        saida,
        gerador: novoGerador(),
        timer: 0,
        proximo: agora + 0.1,
    };
    t.timer = window.setInterval(() => {
        while (t.proximo < c.currentTime + ANTECEDENCIA) {
            agendarCompasso(c, t.saida, t.gerador, t.proximo);
            t.proximo += COMPASSO;
        }
    }, 150);
    atual = t;
}

function parar() {
    if (!atual) {
        return;
    }

    const { palco, saida, timer } = atual;
    window.clearInterval(timer);
    const agora = palco.ctx.currentTime;
    saida.bus.gain.cancelScheduledValues(agora);
    saida.bus.gain.setTargetAtTime(0, agora, 0.12);
    window.setTimeout(() => saida.bus.disconnect(), 1200);
    atual = null;
}

function aplicar() {
    const palco = palcoAtivo();
    const deve = ativa && retidos > 0 && !document.hidden && palco !== null;

    if (deve && !atual) {
        iniciar(palco);
    } else if (!deve && atual) {
        parar();
    }
}

function instalar() {
    if (instalado || typeof document === 'undefined') {
        return;
    }

    instalado = true;
    aoMudarContexto(aplicar);
    document.addEventListener('visibilitychange', aplicar);
}

/** Liga/desliga a música (preferência do aluno já combinada com o som de efeitos). */
export function definirMusicaAtiva(valor: boolean) {
    instalar();
    ativa = valor;
    aplicar();
}

/** Marca que uma tela quer música; o retorno solta. A música só para se ninguém a quiser por 0,5 s, então sobrevive às visitas Inertia. */
export function reterMusica(): () => void {
    instalar();
    retidos += 1;
    aplicar();

    return () => {
        retidos -= 1;
        window.setTimeout(aplicar, 500);
    };
}
