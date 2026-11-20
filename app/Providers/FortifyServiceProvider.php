<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Enums\Serie;
use App\Http\Responses\RecuperacaoSemRevelarEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, RecuperacaoSemRevelarEmail::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Entrar', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Cadastro', [
            'series' => array_map(fn (Serie $serie) => $serie->value, Serie::cases()),
            'idade_minima' => (int) config('gabaritu.idade_minima'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/EsqueciSenha', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/RedefinirSenha', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Por IP com folga para a sala de aula atrás do mesmo NAT; por e-mail para não virar canhão de spam.
        RateLimiter::for('cadastro', fn (Request $request) => [
            Limit::perMinute(10)->by('cadastro|'.$request->ip()),
            Limit::perHour(60)->by('cadastro-hora|'.$request->ip()),
        ]);

        RateLimiter::for('recuperacao-senha', fn (Request $request) => [
            Limit::perMinute(5)->by('recuperacao|'.$request->ip()),
            Limit::perHour(5)->by('recuperacao-email|'.Str::lower((string) $request->input('email'))),
        ]);

    }
}
