import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AdminAuthLayout from '@/layouts/AdminAuthLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AlunoLayout from '@/layouts/AlunoLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ResponsavelLayout from '@/layouts/ResponsavelLayout.vue';
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
            case ['admin/Login', 'admin/DoisFatores'].includes(name):
                return AdminAuthLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name === 'Questao':
                return QuestaoLayout;
            case [
                'responsavel/Entrar',
                'responsavel/EsqueciSenha',
                'responsavel/RedefinirSenha',
            ].includes(name):
                return AuthLayout;
            case name.startsWith('responsavel/'):
                return ResponsavelLayout;
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
