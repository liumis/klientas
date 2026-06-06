<?php

namespace App\Providers;

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
        \Carbon\Carbon::setLocale('lt');
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
