<?php

namespace App\Providers;

use App\Service\Contracts\ChannelsService;
use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesService;
use App\Service\Implements\ChannelsServiceImpl;
use App\Service\Implements\EnvironmentsServiceImpl;
use App\Service\Implements\NamespacesServiceImpl;
use App\Service\Implements\ServicesServiceImpl;
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

        $this->app->bind(
            EnvironmentsService::class,
            EnvironmentsServiceImpl::class
        );

        $this->app->bind(
            ChannelsService::class,
            ChannelsServiceImpl::class
        );

        $this->app->bind(
            ServicesService::class,
            ServicesServiceImpl::class
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
