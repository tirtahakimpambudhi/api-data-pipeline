<?php

namespace App\Providers;

use App\Service\Contracts\NamespacesService;
use App\Service\Implements\NamespacesServiceImpl;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            NamespacesService::class,
            NamespacesServiceImpl::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
