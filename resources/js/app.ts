import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AlunoLayout from '@/layouts/AlunoLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import QuestaoLayout from '@/layouts/QuestaoLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { instalarDestravamento } from '@/lib/motorSom';
import { iniciarSomGlobal } from '@/composables/useMusica';
import { instalarSomInterface } from '@/lib/somInterface';

const appName = 'Gabaritu';

void createInertiaApp({
    title: (title) =>
        !title || title.startsWith(appName)
            ? title || appName
            : `${title} · ${appName}`,
    layout: (name) => {
        switch (true) {
            case name === 'Landing':
                return null;
            case name === 'Questao':
                return QuestaoLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return AlunoLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#e2640a',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
instalarDestravamento();
instalarSomInterface();
iniciarSomGlobal();

// This will listen for flash toast data from the server...
initializeFlashToast();
