<?php

namespace App\Providers;

use App\Models\Spot;
use App\Observers\SpotObserver;
use Illuminate\Support\ServiceProvider;

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
        Spot::observe(SpotObserver::class);
    }
}
