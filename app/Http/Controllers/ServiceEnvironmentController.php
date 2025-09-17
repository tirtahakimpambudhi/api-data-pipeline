<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Environments;
use App\Models\Services;
use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Service\Contracts\ServicesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ServiceEnvironmentController extends Controller
{
    protected ServicesEnvironmentsService $serviceEnvironmentsService;
    protected ServicesService $servicesService;
    protected EnvironmentsService $environmentsService;

    public function __construct(
        ServicesEnvironmentsService $serviceEnvironmentsService,
        ServicesService $servicesService,
        EnvironmentsService $environmentsService
    ) {
        $this->serviceEnvironmentsService = $serviceEnvironmentsService;
        $this->servicesService = $servicesService;
        $this->environmentsService = $environmentsService;
    }

    public function index(PaginationRequest $request)
    {
        try {
            $serviceEnvironments = $this->serviceEnvironmentsService->getAll($request);
            return Inertia::render('service-environment/index', [
                'serviceEnvironments' => $serviceEnvironments,
                'filters' => $request->all(['page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Internal server error');
        }
    }


    public function search(SearchPaginationRequest $request)
    {
        try {
            $serviceEnvironments = $this->serviceEnvironmentsService->search($request);

            return Inertia::render('service-environment/index', [
                'serviceEnvironments' => $serviceEnvironments,
                'filters' => $request->all(['search', 'page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Internal server error');
        }
    }


    public function create()
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user->hasPermission(ResourcesTypes::SERVICES_ENVIRONMENTS, ActionsTypes::CREATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create services environments.');
            };
            $services = $this->servicesService->getAll(null, false);
            $envs = $this->environmentsService->getAll(null, true);
            return Inertia::render('service-environment/create', [
                'services' => $services,
                'environments' => $envs,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('service-environments.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('service-environments.index')->with('error', 'Internal server error');
        }
    }


    public function store(CreateServiceEnvironmentRequest $request): RedirectResponse
    {
        try {
            $this->serviceEnvironmentsService->create($request);
            return redirect()->route('service-environments.index')->with('message', 'Service Environment successfully created.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return  back()->with('error', $e->getMessage())->withInput()->with('error', 'Internal server error, Failed to create service-environment please try again later.');
        }
    }


    public function edit(int $id)
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user->hasPermission(ResourcesTypes::SERVICES_ENVIRONMENTS, ActionsTypes::UPDATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update services environments.');
            };
            $services = $this->servicesService->getAll(null, true);
            $envs = $this->environmentsService->getAll(null, true);
            $svcEnv = $this->serviceEnvironmentsService->getById($id);
            return Inertia::render('service-environment/edit', [
                'services' => $services,
                'environments' => $envs,
                'serviceEnvironment' => $svcEnv,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('service-environments.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('service-environments.index')->with('error', 'Internal server error');
        }
    }


    public function show(int $id)
    {
        try {
            $serviceEnvironment = $this->serviceEnvironmentsService->getById($id);
            return Inertia::render('service-environment/show', [
                'serviceEnvironment' => $serviceEnvironment,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('service-environments.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('service-environments.index')->with('error', 'Internal server error');
        }
    }

    public function update(UpdateServiceEnvironmentRequest $request, int $id): RedirectResponse
    {
        try {
            $this->serviceEnvironmentsService->update($id, $request);
            return redirect()->route('service-environments.index')->with('message', 'Service Environment successfully updated.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return  back()->with('error', $e->getMessage())->withInput()->with('error', 'Internal server error, Failed to update service-environment please try again later.');
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->serviceEnvironmentsService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
