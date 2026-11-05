<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Overtime;
use App\Observers\OvertimeObserver;
use Illuminate\Support\Facades\Gate;
use App\Support\Approvals\ApprovalInbox;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Subscription state is worked out once per request.
        $this->app->scoped(\App\Support\Subscription::class);

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

        // Salary amounts: one rule for every screen (employee list, category page, ...).
        // Managers see staff and priorities, never pay.
        Gate::define('view-salaries', fn ($user) => ! $user->hasRole(['Manager']));

        // Approvals waiting for the signed-in user: the bell in the top bar and the strip on
        // the dashboards. A failure here must never break a page, so it falls back to nothing.
        View::composer(['partials.approvals_bell', 'dashboards._approvals'], function ($view) {
            $summary = null;
            try {
                $summary = ApprovalInbox::for(Auth::user())?->summary();
            } catch (\Throwable $e) {
                report($e);
            }
            $view->with('approvalSummary', $summary);
        });

        // Any change to an approvable record refreshes everyone's counts.
        foreach ([
            \App\Models\Expense::class, Overtime::class, \App\Models\FuelEntry::class, \App\Models\VehicleMaintenance::class,
            \App\Models\Receipt::class, \App\Models\Client::class, \App\Models\employee::class,
        ] as $model) {
            $model::saved(fn () => ApprovalInbox::flush());
            $model::deleted(fn () => ApprovalInbox::flush());
        }
    }
}
