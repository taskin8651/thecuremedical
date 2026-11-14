<?php

namespace App\Providers;

use App\Console\Commands\CheckExpiry;
use App\Services\CartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if ($this->app->runningInConsole()) {
            $this->commands([CheckExpiry::class]);
        }

        // Bell icon in the admin top bar.
        View::composer('admin.partials.topbar', function ($view) {
            $user = auth()->user();
            $view->with([
                'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
                'latestNotifications' => $user ? $user->notifications()->latest()->limit(6)->get() : collect(),
            ]);
        });

        View::composer('site.layout', function ($view) {
            $view->with('cartCount', app(CartService::class)->count(request()));
        });
    }
}
