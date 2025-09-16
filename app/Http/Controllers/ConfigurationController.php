<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\Configurations\CreateConfigurationRequest;
use App\Http\Requests\Configurations\UpdateConfigurationRequest;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Channels;
use App\Models\ServicesEnvironments;
use App\Service\Contracts\ChannelsService;
use App\Service\Contracts\ConfigurationsService;
use App\Service\Contracts\ServicesEnvironmentsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ConfigurationController extends Controller
{
    protected ConfigurationsService $configurationsService;
    protected ServicesEnvironmentsService $servicesEnvironmentsService;
    protected ChannelsService $channelsService;

    public function __construct(
        ConfigurationsService $configurationsService,
        ServicesEnvironmentsService $servicesEnvironmentsService,
        ChannelsService $channelsService
    ) {
        $this->configurationsService = $configurationsService;
        $this->servicesEnvironmentsService = $servicesEnvironmentsService;
        $this->channelsService = $channelsService;
    }

    public function index(PaginationRequest $request)
    {
        try {
            $configurations = $this->configurationsService->getAll($request);

            return Inertia::render('configuration/index', [
                'configurations' => $configurations,
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
            $configurations = $this->configurationsService->search($request);

            return Inertia::render('configuration/index', [
                'configurations' => $configurations,
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
            $user = Auth::guard("web")->user();
            if (!$user->hasPermission(ResourcesTypes::CONFIGURATIONS, ActionsTypes::CREATE)) {
                return redirect()->route('configurations.index')->with('error', 'User doesn\'t have permission to create configurations.');
            }
            $serviceEnvironment = $this->servicesEnvironmentsService->getAll(null, false);
            $channels = $this->channelsService->getAll(null, true);
            return Inertia::render('configuration/create', [
                'serviceEnvironments' => $serviceEnvironment,
                'channels' => $channels,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('configurations.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('configurations.index')->with('error', 'Internal server error');
        }
    }


    public function store(CreateConfigurationRequest $request): RedirectResponse
    {
        try {
            $this->configurationsService->create($request);
            return redirect()->route('configurations.index')->with('message', 'Configuration successfully created.');
        }  catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to create configuration.')->withInput();
        }
    }

    public function show(int $id)
    {
        try {
            $configuration = $this->configurationsService->getById($id);
            return Inertia::render('configuration/show', [
                'configuration' => $configuration,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('configurations.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('configurations.index')->with('error', 'Internal server error');
        }
    }

    public function edit(int $id)
    {
        try {
            $user = Auth::guard("web")->user();
            if (!$user->hasPermission(ResourcesTypes::CONFIGURATIONS, ActionsTypes::UPDATE)) {
                return redirect()->route('configurations.index')->with('error', 'User doesn\'t have permission to update configurations.');
            }
            $serviceEnvironment = $this->servicesEnvironmentsService->getAll(null, false);
            $channels = $this->channelsService->getAll(null, true);
            $configuration = $this->configurationsService->getById($id);
            return Inertia::render('configuration/edit', [
                'serviceEnvironments' => $serviceEnvironment,
                'channels' => $channels,
                'configuration' => $configuration,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('configurations.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('configurations.index')->with('error', 'Internal server error');
        }
    }

    public function update(UpdateConfigurationRequest $request, int $id): RedirectResponse
    {
        try {
            $this->configurationsService->update($id, $request);
            return redirect()->route('configurations.index')->with('message', 'Configuration successfully updated.');
        }  catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to update configuration.')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->configurationsService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
