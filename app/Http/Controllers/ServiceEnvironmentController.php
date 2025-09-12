<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest; // <-- KUNCI PENTING 
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Environments;
use App\Models\Services;
use App\Service\Contracts\EnvironmentsService;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Service\Contracts\ServicesService;
use Illuminate\Http\RedirectResponse;
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

    public function index(PaginationRequest $request): Response
    {
        $serviceEnvironments = $this->serviceEnvironmentsService->getAll($request);

        return Inertia::render('service-environment/index', [
            'serviceEnvironments' => $serviceEnvironments,
            'services' => Services::all(),
            'environments' => Environments::all(),
            'filters' => $request->all(['page', 'size']),
        ]);
    }
    
    // ... method search tetap sama ...
    public function search(SearchPaginationRequest $request): Response
    {
        $serviceEnvironments = $this->serviceEnvironmentsService->search($request);

        return Inertia::render('service-environment/index', [
            'serviceEnvironments' => $serviceEnvironments,
            'services' => Services::all(),
            'environments' => Services::all(),
            'filters' => $request->all(['search', 'page', 'size', 'service_id', 'environment_id']),
        ]);
    }


    public function create(): Response
    {

        return Inertia::render('service-environment/create', [
            'services' => Services::all(),
            'environments' => Environments::all(),
        ]);
    }
    
    // ... method store tetap sama ...
    public function store(CreateServiceEnvironmentRequest $request): RedirectResponse
    {
        try {
            $this->serviceEnvironmentsService->create($request);
            return redirect()->route('service-environments.index')->with('message', 'Service Environment berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['service_id' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to create service-environment: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat relasi.')->withInput();
        }
    }


    public function edit(int $id): Response
    {
        try {
            $serviceEnvironment = $this->serviceEnvironmentsService->getById($id);

            return Inertia::render('service-environment/edit', [
                'serviceEnvironment' => $serviceEnvironment,
                'services' => Services::all(),
                'environments' => Environments::all(),
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    // ... sisa method (show, update, destroy) tidak perlu diubah ...
    public function show(int $id): Response
    {
        try {
            $serviceEnvironment = $this->serviceEnvironmentsService->getById($id);
            return Inertia::render('service-environment/show', [
                'serviceEnvironment' => $serviceEnvironment,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function update(UpdateServiceEnvironmentRequest $request, int $id): RedirectResponse
    {
        try {
            $this->serviceEnvironmentsService->update($id, $request);
            return redirect()->route('service-environments.index')->with('message', 'Service Environment berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['service_id' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to update service-environment {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui relasi.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->serviceEnvironmentsService->delete($id);
            return redirect()->route('service-environments.index')->with('message', 'Service Environment berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error("Failed to delete service-environment {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus relasi.');
        }
    }
}