<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind('path.public', function () {
            // Adjust the path based on your exact file structure
            return base_path('public_html'); 
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // Paginator::useBootstrapFive();
        Schema::defaultStringLength(200);
        
    }
}
