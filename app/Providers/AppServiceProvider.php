<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        // The landing page is driven by config/payforgoals.php. Bind the pieces
        // it needs here so the route stays a plain view route.
        View::composer('landing', function ($view) {
            $cfg = config('payforgoals');

            $view->with([
                'brand' => $cfg['brand'],
                'price' => $cfg['pricing'],
                'tempo' => $cfg['tempo'],
                'links' => $cfg['links'],
            ]);
        });
    }
}
