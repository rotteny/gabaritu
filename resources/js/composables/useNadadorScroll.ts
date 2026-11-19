import { onBeforeUnmount, onMounted } from 'vue';
import type { Ref } from 'vue';

type Alvos = {
    corpo: Ref<HTMLElement | null>;
    cauda: () => SVGGraphicsElement | null;
    aoVelocidade?: (velocidade: number) => void;
};

const limitar = (v: number, max: number) => Math.max(-max, Math.min(max, v));

/**
 * Faz o nadador acompanhar a rolagem como uma mola: atrasa, inclina e bate a cauda
 * proporcional à velocidade. Só transform, um listener passivo e rAF que dorme quando para.
 */
export function useNadadorScroll(
    { corpo, cauda, aoVelocidade }: Alvos,
    parado: () => boolean,
) {
    let raf = 0;
    let ultimoY = 0;
    let ultimoT = 0;
    let vel = 0;
    let off = 0;
    let offV = 0;
    let fase = 0;
    let yConhecido = window.scrollY;
    let ultimoScroll = 0;

    function quadro(t: number) {
        const el = corpo.value;
        const dt = Math.min((t - ultimoT) / 1000, 0.05) || 0.016;
        const y = window.scrollY;
        ultimoT = t;
        vel += ((y - ultimoY) / dt - vel) * 0.2;
        ultimoY = y;
        const alvo = limitar(-vel * 0.05, 70);
        offV += ((alvo - off) * 60 - offV * 9) * dt;
        off += offV * dt;
        const rapidez = Math.min(Math.abs(vel) / 1500, 1);
        fase += dt * (2 + rapidez * 12) * Math.PI * 2;

        if (el) {
            el.style.transform = `translate3d(0, ${off.toFixed(1)}px, 0) rotate(${limitar(vel * 0.012, 22).toFixed(1)}deg)`;
            cauda()?.style.setProperty(
                'transform',
                `rotate(${(Math.sin(fase) * (10 + rapidez * 14)).toFixed(1)}deg)`,
            );
        }

        aoVelocidade?.(Math.abs(vel));

        if (
            t - ultimoScroll > 150 &&
            Math.abs(vel) < 2 &&
            Math.abs(off) < 0.3 &&
            Math.abs(offV) < 1
        ) {
            repousar();

            return;
        }

        raf = requestAnimationFrame(quadro);
    }

    function repousar() {
        raf = 0;
        vel = off = offV = 0;
        corpo.value?.style.removeProperty('transform');
        cauda()?.style.removeProperty('transform');
        corpo.value?.classList.remove('nadador--nadando');
    }

    function acordar() {
        ultimoScroll = performance.now();
        const anterior = yConhecido;
        yConhecido = window.scrollY;

        if (raf || parado()) {
            return;
        }

        corpo.value?.classList.add('nadador--nadando');
        ultimoY = anterior;
        ultimoT = ultimoScroll - 16;
        raf = requestAnimationFrame(quadro);
    }

    onMounted(() =>
        window.addEventListener('scroll', acordar, { passive: true }),
    );
    onBeforeUnmount(() => {
        window.removeEventListener('scroll', acordar);
        cancelAnimationFrame(raf);
    });
}
