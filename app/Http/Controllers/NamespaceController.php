<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Service\Contracts\NamespacesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NamespaceController extends Controller
{
    protected NamespacesService $namespacesService;

    public function __construct(NamespacesService $namespacesService)
    {
        $this->namespacesService = $namespacesService;
    }

    public function index(PaginationRequest $request): Response
    {
        $namespaces = $this->namespacesService->getAll($request);

        return Inertia::render('namespace/index', [
            'namespaces' => $namespaces,
            'filters' => $request->all(['page', 'size']),
        ]);
    }

    public function search(SearchPaginationRequest $request): Response
    {
        $namespaces = $this->namespacesService->search($request);

        return Inertia::render('namespace/index', [
            'namespaces' => $namespaces,
            'filters' => $request->all(['search', 'page', 'size']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('namespace/create');
    }

    public function store(CreateNamespaceRequest $request): RedirectResponse
    {
        try {
            $this->namespacesService->create($request);
            return redirect()->route('namespaces.index')->with('message', 'Namespace berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat namespace.')->withInput();
        }
    }

    public function show(int $id): Response
    {
        try {
            $namespace = $this->namespacesService->getById($id);
            return Inertia::render('namespace/show', [
                'namespace' => $namespace,
            ]);
        } catch (NotFoundServiceException $e) {
            abort(404);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $namespace = $this->namespacesService->getById($id);
            return Inertia::render('namespace/edit', [
                'namespace' => $namespace,
            ]);
        } catch (NotFoundServiceException $e) {
            abort(404);
        }
    }

    public function update(UpdateNamespaceRequest $request, int $id): RedirectResponse
    {
        try {
            $this->namespacesService->update($id, $request);
            return redirect()->route('namespaces.index')->with('message', 'Namespace berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui namespace.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->namespacesService->delete($id);
            return redirect()->route('namespaces.index')->with('message', 'Namespace berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus namespace.');
        }
    }
    
    public function storeService(CreateServiceRequest $request, int $namespaceId): RedirectResponse
    {
        try {
            $this->namespacesService->createService($namespaceId, $request);
            return redirect()->route('namespaces.show', $namespaceId)->with('message', 'Service berhasil ditambahkan.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menambahkan service.')->withInput();
        }
    }
}