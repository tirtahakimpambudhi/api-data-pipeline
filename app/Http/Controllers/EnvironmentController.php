<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Environments\CreateEnvironmentRequest;
use App\Http\Requests\Environments\UpdateEnvironmentRequest;
use App\Service\Contracts\EnvironmentsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentsService
     */
    protected EnvironmentsService $environmentsService;

    public function __construct(EnvironmentsService $environmentsService)
    {
        $this->environmentsService = $environmentsService;
    }

    public function index(PaginationRequest $request): Response
    {
        $environments = $this->environmentsService->getAll($request);

        dd($environments);

        return Inertia::render('environment/index', [
            'environments' => $environments,
            'filters' => $request->all(['page', 'size']),
        ]);
    }

    public function search(SearchPaginationRequest $request): Response
    {
        $environments = $this->environmentsService->search($request);

        return Inertia::render('environment/index', [
            'environments' => $environments,
            'filters' => $request->all(['search', 'page', 'size']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('environment/create');
    }

    public function store(CreateEnvironmentRequest $request): RedirectResponse
    {
        try {
            $this->environmentsService->create($request);
            return redirect()->route('environments.index')->with('message', 'Environment berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat environment.')->withInput();
        }
    }

    public function show(int $id): Response
    {
        try {
            $environment = $this->environmentsService->getById($id);
            return Inertia::render('environment/show', [
                'environment' => $environment,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $environment = $this->environmentsService->getById($id);
            return Inertia::render('environment/edit', [
                'environment' => $environment,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function update(UpdateEnvironmentRequest $request, int $id): RedirectResponse
    {
        try {
            $this->environmentsService->update($id, $request);
            return redirect()->route('environments.index')->with('message', 'Environment berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui environment.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->environmentsService->delete($id);
            return redirect()->route('environments.index')->with('message', 'Environment berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus environment.');
        }
    }
}