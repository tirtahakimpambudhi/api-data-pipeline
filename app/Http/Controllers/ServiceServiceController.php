<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Namespaces;
use App\Service\Contracts\NamespacesService;
use App\Service\Contracts\ServicesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ServiceServiceController extends Controller
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

    public function index(PaginationRequest $request): Response
    {
        $services = $this->servicesService->getAll($request);

        return Inertia::render('service/index', [
            'services' => $services,
            'filters' => $request->all(['page', 'size']),
        ]);
    }

    public function search(SearchPaginationRequest $request): Response
    {
        $services = $this->servicesService->search($request);

        return Inertia::render('service/index', [
            'services' => $services,
            'filters' => $request->all(['search', 'page', 'size']),
        ]);
    }

    public function create(PaginationRequest $request): Response
    {

        
        return Inertia::render('service/create', [
            'namespaces' => Namespaces::all()
        ]);
    }

    public function store(CreateServiceRequest $request): RedirectResponse
    {
        try {
            $this->servicesService->create($request);
            return redirect()->route('services.index')->with('message', 'Service berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to create service: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat service.')->withInput();
        }
    }

    public function show(int $id): Response
    {
        try {
            $service = $this->servicesService->getById($id);
            return Inertia::render('service/show', [
                'service' => $service,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $service = $this->servicesService->getById($id);

            return Inertia::render('service/edit', [
                'service' => $service,
                'namespaces' => Namespaces::all(),
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function update(UpdateServiceRequest $request, int $id): RedirectResponse
    {
        try {
            $this->servicesService->update($id, $request);
            return redirect()->route('services.index')->with('message', 'Service berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to update service {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui service.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->servicesService->delete($id);
            return redirect()->route('services.index')->with('message', 'Service berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error("Failed to delete service {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus service.');
        }
    }
}