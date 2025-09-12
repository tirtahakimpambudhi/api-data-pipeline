<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Channels\CreateChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Service\Contracts\ChannelsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ChannelController extends Controller
{
    /**
     * @var ChannelsService
     */
    protected ChannelsService $channelsService;

    public function __construct(ChannelsService $channelsService)
    {
        $this->channelsService = $channelsService;
    }

    public function index(PaginationRequest $request): Response
    {
        $channels = $this->channelsService->getAll($request);

        return Inertia::render('channel/index', [
            'channels' => $channels,
            'filters' => $request->all(['page', 'size']),
        ]);
    }

    public function search(SearchPaginationRequest $request): Response
    {
        $channels = $this->channelsService->search($request);

        return Inertia::render('channel/index', [
            'channels' => $channels,
            'filters' => $request->all(['search', 'page', 'size']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('channel/create');
    }

    public function store(CreateChannelRequest $request): RedirectResponse
    {
        try {
            $this->channelsService->create($request);
            return redirect()->route('channels.index')->with('message', 'Channel berhasil dibuat.');
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to create channel: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal membuat channel.')->withInput();
        }
    }

    public function show(int $id): Response
    {
        try {
            $channel = $this->channelsService->getById($id);
            return Inertia::render('channel/show', [
                'channel' => $channel,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $channel = $this->channelsService->getById($id);
            return Inertia::render('channel/edit', [
                'channel' => $channel,
            ]);
        } catch (NotFoundServiceException) {
            abort(404);
        }
    }

    public function update(UpdateChannelRequest $request, int $id): RedirectResponse
    {
        try {
            $this->channelsService->update($id, $request);
            return redirect()->route('channels.index')->with('message', 'Channel berhasil diperbarui.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ConflictServiceException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            Log::error("Failed to update channel {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal memperbarui channel.')->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->channelsService->delete($id);
            return redirect()->route('channels.index')->with('message', 'Channel berhasil dihapus.');
        } catch (NotFoundServiceException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error("Failed to delete channel {$id}: {$e->getMessage()}", ['exception' => $e]);
            return back()->with('error', 'Terjadi kesalahan internal. Gagal menghapus channel.');
        }
    }
}