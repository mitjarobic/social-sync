<?php

namespace App\Providers;


use Laravel\Horizon\Horizon;
use App\Support\TimezoneHelper;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // No services to register
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set the application timezone based on the user's preference
        TimezoneHelper::setApplicationTimezone();

        // Register the metrics-bar component
        Blade::component('filament.components.metrics-bar', 'filament::metrics-bar');

        // Horizon
        Horizon::auth(function ($request) {
            
            $user = auth()->guard('filament')->user();
            return $user && $user->id === 1;
        });

        Horizon::routeMailNotificationsTo('email@example.com');
    }
}
