<?php

namespace App\Providers;


use App\Service\Contracts\TransformService;
use App\Service\Implements\TransformServiceImpl;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;


use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use App\Service\Contracts\ChannelsService;
use App\Service\Contracts\ConfigurationsService;
use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Service\Contracts\ServicesService;
use App\Service\Implements\ChannelsServiceImpl;
use App\Service\Implements\ConfigurationsServiceImpl;
use App\Service\Implements\EnvironmentsServiceImpl;
use App\Service\Implements\NamespacesServiceImpl;
use App\Service\Implements\ServicesEnvironmentsServiceImpl;
use App\Service\Implements\ServicesServiceImpl;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Log\Logger;
use Mustache\Engine;

class ServiceLayerProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        // Namespaces
        $this->app->bind(NamespacesService::class, function ($app) {
            return new NamespacesServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Namespaces::class),
                $app->make(Logger::class),
            );
        });

        // Services
        $this->app->bind(ServicesService::class, function ($app) {
            return new ServicesServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Services::class),
                $app->make(Logger::class),
            );
        });

        // Environments
        $this->app->bind(EnvironmentsService::class, function ($app) {
            return new EnvironmentsServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Environments::class),
                $app->make(Logger::class),
            );
        });

        // ServicesEnvironments
        $this->app->bind(ServicesEnvironmentsService::class, function ($app) {
            return new ServicesEnvironmentsServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(ServicesEnvironments::class),
                $app->make(Logger::class),
            );
        });

        // Channels
        $this->app->bind(ChannelsService::class, function ($app) {
            return new ChannelsServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Channels::class),
                $app->make(Logger::class),
            );
        });


        // Transforms
        $this->app->bind(TransformService::class, function ($app) {
            return new TransformServiceImpl(
              $app->make(Engine::class),
              $app->make(Logger::class),
            );
        });

        // Configurations
        $this->app->bind(ConfigurationsService::class, function ($app) {
            return new ConfigurationsServiceImpl(
                $app->make(AuthFactory::class),
                $app->make(Configurations::class),
                $app->make(Logger::class),
                $app->make(TransformService::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }

    public function provides(): array
    {
        return [
            NamespacesService::class,
            ServicesService::class,
            EnvironmentsService::class,
            ServicesEnvironmentsService::class,
            ChannelsService::class,
            ConfigurationsService::class,
            TransformService::class
        ];
    }
}
