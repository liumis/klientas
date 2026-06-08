<?php

namespace App\Providers;

use App\Models\Claim;
use App\Observers\ClaimObserver;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MicrosoftGraphMailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Claim::observe(ClaimObserver::class);

        \Carbon\Carbon::setLocale('lt');
        app()->setLocale('lt');
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
