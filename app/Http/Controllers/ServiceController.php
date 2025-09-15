<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ServiceController extends Controller
{
    /**
     * @var ServicesService
     * @var NamespacesService
     */
    protected ServicesService $servicesService;
    protected NamespacesService $namespacesService;

    public function __construct(ServicesService $servicesService, NamespacesService $namespacesService)
    {
        $this->servicesService = $servicesService;
        $this->namespacesService = $namespacesService;
    }

    public function index(PaginationRequest $request)
    {
        try {
            $services = $this->servicesService->getAll($request);
            return Inertia::render('service/index', [
                'services' => $services,
                'filters' => $request->all(['page', 'size']),
                'errors'     => null,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('service/index', [
                'services' => [],
                'filters'    => $request->all(['page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('service/index', [
                'services' => [],
                'filters'    => $request->all(['page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
            ]);
            return $this->inertiaWithStatus($resp, 500);
        }

    }

    public function search(SearchPaginationRequest $request)
    {
        try {
            $services = $this->servicesService->search($request);
            return Inertia::render('service/index', [
                'services' => $services,
                'filters' => $request->all(['search','page', 'size']),
                'errors'     => null,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('service/index', [
                'services' => $this->emptyPaginated(),
                'filters'    => $request->all(['search','page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('service/index', [
                'services' => $this->emptyPaginated(),
                'filters'    => $request->all(['search', 'page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
            ]);
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function create()
    {
        try {
            $namespaces = $this->namespacesService->getAll(null, true);
            return Inertia::render('service/create', [
                'namespaces' => $namespaces,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('services.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('services.index')->with('error', 'Internal server error.');
        }

    }

    public function store(CreateServiceRequest $request): RedirectResponse
    {
        try {
            $this->servicesService->create($request);
            return redirect()->route('services.index')->with('message', 'Service created successfully');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            if ($e instanceof ConflictServiceException) {
                return back()->withErrors(['name' => $e->getMessage()])->with('error', $e->getMessage())->withInput();
            }
            return back()->withErrors(['name' => $e->getMessage()])->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to create service please try again')->withInput();
        }
    }

    public function show(int $id): Response | RedirectResponse
    {
        try {
            $service = $this->servicesService->getById($id);
            return Inertia::render('service/show', [
                'service' => $service,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                return redirect()->route('services.index')->with('error', "Service with id {$id} not found");
            }
            return redirect()->route('services.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('services.index')->with('error', 'Internal server error, Failed to show service please try again.');
        }
    }

    public function edit(int $id): Response | RedirectResponse
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user->hasPermission(ResourcesTypes::SERVICES, ActionsTypes::UPDATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update services.');
            };
            $namespaces = $this->namespacesService->getAll(null, true);
            $service = $this->servicesService->getById($id);

            return Inertia::render('service/edit', [
                'service' => $service,
                'namespaces' =>  $namespaces,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                return redirect()->route('services.index')->with('error', "Service with id {$id} not found");
            }
            return redirect()->route('services.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('services.index')->with('error', 'Internal server error.');
        }
    }

    public function update(UpdateServiceRequest $request, int $id): RedirectResponse
    {
        try {
            $this->servicesService->update($id, $request);
            return redirect()->route('services.index')->with('message', 'Service updated successfully');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                return redirect()->route('services.index')->with('error', "Service with id {$id} not found");
            }
            if ($e instanceof ConflictServiceException) {
                return back()->withErrors(['name' => $e->getMessage()])->with('error', $e->getMessage())->withInput();
            }
            return back()->withErrors(['name' => $e->getMessage()])->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to update service please try again')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->servicesService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
