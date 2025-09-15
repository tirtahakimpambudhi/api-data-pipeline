<?php

namespace App\Providers;

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
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

use Illuminate\Log\Logger;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\Environments;
use App\Models\ServicesEnvironments;
use App\Models\Channels;
use App\Models\Configurations;

class ServiceLayerProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        //
    }

    public function provides(): array
    {
        return [

        ];
    }
}
