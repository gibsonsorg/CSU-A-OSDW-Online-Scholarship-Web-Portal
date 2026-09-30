<?php

namespace App\Providers;

use App\Services\AdminDashboardStats;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;

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
        View::composer('admin.welcome', function (ViewInstance $view): void {
            $view->with('stats', app(AdminDashboardStats::class)->get());
        });
    }
}
