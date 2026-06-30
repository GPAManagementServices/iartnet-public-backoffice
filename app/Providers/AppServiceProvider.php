<?php

namespace App\Providers;

use App\Policies\MediaPolicy;
use App\Support\EditorialHtmlSanitizer;
use Awcodes\Curator\Models\Media;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EditorialHtmlSanitizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forza https solo in produzione
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('media-sign', function (Request $request) {
            $max = max(1, (int) config('media.sign_max_attempts_per_minute', 120));

            return Limit::perMinute($max)->by($request->ip());
        });

        // Curator: abilita accesso ai media (serve per /curator/{path})
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
