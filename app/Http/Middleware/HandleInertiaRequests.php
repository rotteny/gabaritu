<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\DoisFatoresController;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->alunoLogado(),
            ],
            'admin' => $this->admin($request),
            'gabaritu' => [
                'email_contato' => config('gabaritu.email_contato'),
                'repositorio_url' => config('gabaritu.repositorio_url'),
                'cadastro_aberto' => (bool) config('gabaritu.cadastro_aberto'),
            ],
        ];
    }

    /**
     * Sempre o guard do site: o do painel admin tem conta e sessão separadas.
     *
     * @return array<string, mixed>|null
     */
    private function alunoLogado(): ?array
    {
        $usuario = Auth::guard('web')->user();

        return $usuario instanceof User ? $usuario->paraCompartilhar() : null;
    }

    /**
     * Só existe sob o prefixo do admin e com admin logado. As rotas vêm prontas do servidor,
     * porque o prefixo é configurável em produção e não pode ficar fixo no build do front.
     *
     * @return array<string, mixed>|null
     */
    private function admin(Request $request): ?array
    {
        $prefixo = (string) config('gabaritu.admin_prefixo');
        $admin = $request->is($prefixo, $prefixo.'/*') ? Auth::guard('admin')->user() : null;

        if (! $admin instanceof Admin) {
            return null;
        }

        return [
            'email' => $admin->email,
            'prefixo' => $prefixo,
            'inatividade_minutos' => (int) config('gabaritu.admin.inatividade_minutos'),
            'restantes_recuperacao' => $request->session()->get(DoisFatoresController::FLASH_RESTANTES),
            'rotas' => [
                'dashboard' => route('admin.dashboard', absolute: false),
                'alunos' => route('admin.alunos', absolute: false),
                'responsaveis' => route('admin.responsaveis', absolute: false),
                'questoes' => route('admin.questoes', absolute: false),
                'respostas' => route('admin.respostas', absolute: false),
                'sair' => route('admin.logout', absolute: false),
            ],
        ];
    }
}
