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
        $this->app->singleton(\Google\Service\Drive::class, function ($app) {
            $config = $app['config']->get('filesystems.disks.google', []);
            $folderId = trim((string) ($config['folder'] ?? env('GOOGLE_DRIVE_FOLDER_ID', '')));

            $client = new \Google\Client();
            $client->setClientId($config['clientId'] ?? env('GOOGLE_DRIVE_CLIENT_ID'));
            $client->setClientSecret($config['clientSecret'] ?? env('GOOGLE_DRIVE_CLIENT_SECRET'));
            $client->refreshToken($config['refreshToken'] ?? env('GOOGLE_DRIVE_REFRESH_TOKEN'));

            return new \Google\Service\Drive($client);
        });
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
