<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Service\Contracts\ChannelsService;
use App\Service\Contracts\ConfigurationsService;
use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Service\Contracts\ServicesService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected NamespacesService $namespacesService,
        protected ServicesService $servicesService,
        protected ServicesEnvironmentsService $servicesEnvironmentsService,
        protected EnvironmentsService $environmentsService,
        protected ChannelsService $channelsService,
        protected ConfigurationsService $configurationsService,
    ) {}

    public function index()
    {
        try {
            $user = Auth::guard('web')->user();

            $data = [];

            if ($user->hasPermission(ResourcesTypes::NAMESPACES, ActionsTypes::READ)) {
                $data['namespaces'] = $this->namespacesService->getAll(null, true);
            }

            if ($user->hasPermission(ResourcesTypes::SERVICES, ActionsTypes::READ)) {
                $data['services'] = $this->servicesService->getAll(null, true);
            }

            if ($user->hasPermission(ResourcesTypes::ENVIRONMENTS, ActionsTypes::READ)) {
                $data['environments'] = $this->environmentsService->getAll(null, true);
            }

            if ($user->hasPermission(ResourcesTypes::SERVICES_ENVIRONMENTS, ActionsTypes::READ)) {
                $data['servicesEnvironments'] = $this->servicesEnvironmentsService->getAll(null, true);
            }

            if ($user->hasPermission(ResourcesTypes::CHANNELS, ActionsTypes::READ)) {
                $data['channels'] = $this->channelsService->getAll(null, true);
            }

            if ($user->hasPermission(ResourcesTypes::CONFIGURATIONS, ActionsTypes::READ)) {
                $data['configurations'] = $this->configurationsService->getAll(null, true);
            }

            return Inertia::render('dashboard', [
                'data' => $data,
            ]);
        } catch (AppServiceException|\Throwable $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }

            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', method_exists($e, 'getMessage') ? $e->getMessage() : 'Unauthorized');
        }
    }
}
