<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        Event::listen(Login::class, fn (Login $evento) => $evento->user->forceFill(['ultimo_acesso_em' => now()])->saveQuietly());
    }

    /** Convite e aceite do responsável (docs/responsaveis.md, regra 14). */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('convite-aceite', fn (Request $request) => [
            Limit::perMinute(10)->by('aceite|'.$request->ip()),
            Limit::perHour(30)->by('aceite-token|'.$request->route('token')),
        ]);

        RateLimiter::for('convite-emissao', fn (Request $request) => [
            Limit::perHour(10)->by('emissao|'.$request->user()->id),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
