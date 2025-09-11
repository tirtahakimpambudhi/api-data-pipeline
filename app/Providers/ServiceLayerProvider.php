<?php

namespace App\Providers;

use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\RolesPermissions;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use App\Models\Users;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesService;
use App\Service\Implements\NamespacesServiceImpl;
use App\Service\Implements\ServicesServiceImpl;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Log\Logger;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
class ServiceLayerProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(Users::class, function ($app) {
            return new Users();
        });
        $this->app->bind(Namespaces::class, function ($app) {
            return new Namespaces();
        });
        $this->app->bind(Services::class, function ($app) {
            return new Services();
        });
        $this->app->bind(Environments::class, function ($app) {
            return new Environments();
        });
        $this->app->bind(ServicesEnvironments::class, function ($app) {
            return new ServicesEnvironments();
        });

        $this->app->bind(Channels::class, function ($app) {
            return new Channels();
        });
        $this->app->bind(Configurations::class, function ($app) {
            return new Configurations();
        });
        $this->app->bind(Roles::class, function ($app) {
            return new Roles();
        });

        $this->app->bind(Permissions::class, function ($app) {
            return new Permissions();
        });

        $this->app->bind(RolesPermissions::class, function ($app) {
            return new RolesPermissions();
        });

        $this->app->bind(NamespacesService::class, function ($app) {
            return new NamespacesServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Namespaces::class),
                $app->make(Logger::class),
            );
        });
        $this->app->bind(ServicesService::class, function ($app) {
            return new ServicesServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Namespaces::class),
                $app->make(Logger::class),
            );
        });

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }


}
