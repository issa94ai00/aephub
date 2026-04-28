<?php

namespace App\Providers;

use App\Models\CourseVideo;
use App\Policies\VideoPolicy;
use App\Services\SiteSettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettingsService::class, fn () => new SiteSettingsService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = $this->app->make(SiteSettingsService::class);
        $settings->applyToConfig();
        View::share('site', $settings->all());

        Gate::policy(CourseVideo::class, VideoPolicy::class);

        RateLimiter::for('playback-key', function (Request $request) {
            $userId = (string) ($request->user()?->id ?? 'guest');
            $deviceId = (string) $request->header('X-Device-Id', 'unknown');

            return Limit::perMinute(30)->by($userId.'|'.$deviceId);
        });
    }
}
