<?php

namespace App\Providers;

use App\Models\CourseVideo;
use App\Policies\VideoPolicy;
use App\Services\SiteSettingsService;
use App\Services\StorageDestinationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
        $this->app->singleton(StorageDestinationService::class, fn () => new StorageDestinationService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $settings = $this->app->make(SiteSettingsService::class);
        $settings->applyToConfig();
        View::share('site', $settings->all());

        // After applyToConfig(), so a managed destination that reuses a provider
        // name ("wasabi") wins over the legacy single-provider settings applied
        // over the same disk. Two sources of truth for one disk is how a file
        // ends up written to one bucket and read back from another.
        $this->app->make(StorageDestinationService::class)->registerDisks();

        $compiledViewPath = config('view.compiled') ?: storage_path('framework/views');
        if (! File::isDirectory($compiledViewPath)) {
            File::ensureDirectoryExists($compiledViewPath, 0755);
        }
        config(['view.compiled' => $compiledViewPath]);

        Gate::policy(CourseVideo::class, VideoPolicy::class);

        RateLimiter::for('playback-key', function (Request $request) {
            $userId = (string) ($request->user()?->id ?? 'guest');
            $deviceId = (string) $request->header('X-Device-Id', 'unknown');

            return Limit::perMinute(30)->by($userId.'|'.$deviceId);
        });
    }
}
