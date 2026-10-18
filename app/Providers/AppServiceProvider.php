<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Grade;
use App\Observers\GradeObserver;

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
        Grade::observe(GradeObserver::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Login de la API: por cuenta + IP, para no bloquear a toda una red escolar.
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
