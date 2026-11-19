import { somLigadoRef } from '@/composables/useMusica';
import { blipHover, somDestravado, tokClique } from '@/lib/motorSom';

const SELECIONAVEL =
    'a[href], button, [role="button"], summary, select, label:has(input), input[type="radio"], input[type="checkbox"]';
const INTERVALO_MS = 60;

let instalado = false;
let ultimoHover = 0;
let atual: Element | null = null;

function alvo(origem: EventTarget | null): Element | null {
    const el = origem instanceof Element ? origem.closest(SELECIONAVEL) : null;

    if (!el || el.matches(':disabled, [aria-disabled="true"]')) {
        return null;
    }

    return el;
}

const pode = () => somLigadoRef.value && somDestravado();

/** Sons de interface por delegação: blip ao entrar com o mouse e tok ao clicar. Sem toque. */
export function instalarSomInterface() {
    if (instalado || typeof document === 'undefined') {
        return;
    }

    instalado = true;
    document.addEventListener(
        'pointerover',
        (e) => {
            if (e.pointerType !== 'mouse') {
                return;
            }

            const el = alvo(e.target);

            if (el === atual) {
                return;
            }

            atual = el;
            const agora = performance.now();

            if (el && pode() && agora - ultimoHover >= INTERVALO_MS) {
                ultimoHover = agora;
                blipHover();
            }
        },
        true,
    );
    document.addEventListener(
        'click',
        (e) => {
            const el = alvo(e.target);

            // Alternativas têm som próprio de acerto/erro.
            if (el && !el.closest('.q-alt') && pode()) {
                tokClique();
            }
        },
        true,
    );
}
