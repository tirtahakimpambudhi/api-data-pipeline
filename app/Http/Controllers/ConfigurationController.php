<?php

namespace App\Http\Controllers;

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

    public function index(PaginationRequest $request): Response
    {
        $configurations = $this->configurationsService->getAll($request);

        return Inertia::render('configuration/index', [
            'configurations' => $configurations,
            'serviceEnvironments' => ServicesEnvironments::all(),
            'channels' => Channels::all(),
            'filters' => $request->all(['page', 'size']),
        ]);
    }

    public function search(SearchPaginationRequest $request): Response
    {
        $configurations = $this->configurationsService->search($request);

        return Inertia::render('configuration/index', [
            'configurations' => $configurations,
            'serviceEnvironments' => ServicesEnvironments::all(),
            'channels' => Channels::all(),
            'filters' => $request->all(['search', 'page', 'size', 'service_environment_id', 'channel_id']),
        ]);
    }

    public function create(): Response
    {

        return Inertia::render('configuration/create', [
            'serviceEnvironments' => ServicesEnvironments::all(),
            'channels' => Channels::all(),
        ]);
    }

    public function store(CreateConfigurationRequest $request): RedirectResponse
    {
        try {
            $this->configurationsService->create($request);
            return redirect()->route('configurations.index')->with('message', 'Configuration berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['service_environment_id' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to create configuration: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat konfigurasi.')->withInput();
        }
    }

    public function show(int $id): Response
    {
        try {
            $configuration = $this->configurationsService->getById($id);
            return Inertia::render('configuration/show', [
                'configuration' => $configuration,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $configuration = $this->configurationsService->getById($id);

            return Inertia::render('configuration/edit', [
                'configuration' => $configuration,
                'serviceEnvironments' => ServicesEnvironments::all(),
                'channels' => Channels::all(),
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function update(UpdateConfigurationRequest $request, int $id): RedirectResponse
    {
        try {
            $this->configurationsService->update($id, $request);
            return redirect()->route('configurations.index')->with('message', 'Configuration berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['service_environment_id' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to update configuration {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui konfigurasi.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->configurationsService->delete($id);
            return redirect()->route('configurations.index')->with('message', 'Configuration berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error("Failed to delete configuration {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus konfigurasi.');
        }
    }
}