<?php

namespace App\Providers;

use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\NamespacesService;
use App\Service\Implements\EnvironmentsServiceImpl;
use App\Service\Implements\NamespacesServiceImpl;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
