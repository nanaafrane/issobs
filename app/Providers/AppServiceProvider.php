<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Overtime;
use App\Observers\OvertimeObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        // app()->usePublicPath(__DIR__ . '/../../public_html');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // Paginator::useBootstrapFive();
        Schema::defaultStringLength(200);
        Overtime::observe(OvertimeObserver::class);
    }
}
