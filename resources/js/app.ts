import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AlunoLayout from '@/layouts/AlunoLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import QuestaoLayout from '@/layouts/QuestaoLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { instalarDestravamento } from '@/lib/motorSom';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
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

// This will listen for flash toast data from the server...
initializeFlashToast();
