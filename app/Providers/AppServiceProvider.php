<?php

namespace App\Providers;

use App\Support\MonitoringProtocol;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The protocol file is read once per request.
        $this->app->scoped(MonitoringProtocol::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRoutePatterns();
    }

    /**
     * Only numeric ids reach route model binding.
     *
     * Without this, a path such as /mitigations/create, or any mistyped id,
     * is bound as an id: PostgreSQL then refuses to compare text with a
     * bigint and the request fails with a 500 instead of a 404.
     */
    protected function configureRoutePatterns(): void
    {
        foreach (['ai_system', 'risk', 'adverse_event', 'mitigation', 'owner', 'link', 'evidence', 'status_history', 'reassessment', 'system_change'] as $parameter) {
            Route::pattern($parameter, '[0-9]+');
        }
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
