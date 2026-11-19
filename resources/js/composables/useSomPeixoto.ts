import { toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { HumorFala } from '@/types/gabaritu';

type Tom = {
    f: number;
    f2?: number;
    t?: number;
    v?: number;
    type?: OscillatorType;
    at?: number;
};

const entre = (a: number, b: number) => a + Math.random() * (b - a);

/** Voz em "peixês" (bipes do WebAudio) e efeitos de acerto/erro. Respeita a preferência de som. */
export function useSomPeixoto(ligado: MaybeRefOrGetter<boolean>) {
    let ctx: AudioContext | null = null;

    function destravar() {
        if (!ctx) {
            try {
                ctx = new window.AudioContext();
            } catch {
                ctx = null;
            }
        }

        if (ctx?.state === 'suspended') {
            void ctx.resume();
        }
    }

    function tom({ f, f2, t = 0.08, v = 0.07, type = 'sine', at = 0 }: Tom) {
        if (!ctx || !toValue(ligado)) {
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
        osc.connect(ganho).connect(ctx.destination);
        osc.start(t0);
        osc.stop(t0 + t + 0.03);
    }

    function bipe(humor: HumorFala) {
        if (humor === 'bravo') {
            const f = entre(80, 140);
            tom({ f, f2: f * 0.6, type: 'triangle', v: 0.1, t: 0.07 });
        } else if (humor === 'feliz') {
            const f = entre(520, 820);
            tom({ f, f2: f * 1.5, t: 0.07, v: 0.06 });
        } else {
            const f = entre(250, 360);
            tom({ f, f2: f * 0.7, type: 'triangle', t: 0.07 });
        }
    }

    function acerto() {
        [1, 1.26, 1.5, 2].forEach((m, i) =>
            tom({
                f: 440 * m,
                t: 0.14,
                type: 'triangle',
                v: 0.08,
                at: i * 0.07,
            }),
        );
    }

    function erro() {
        tom({ f: 210, f2: 70, t: 0.22, v: 0.16 });
        tom({ f: 140, f2: 420, t: 0.2, type: 'triangle', at: 0.2 });
    }

    return { destravar, bipe, acerto, erro };
}
