<?php

namespace App\Http\Controllers;

use App\Exceptions\AppServiceException;
use App\Exceptions\InternalServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Channels\CreateChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Service\Contracts\ChannelsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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

    public function index(PaginationRequest $request)
    {
        try {
            $channels = $this->channelsService->getAll($request);

            return Inertia::render('channel/index', [
                'channels' => $channels,
                'filters' => $request->all(['page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('channel/index', [
                'channels' => [],
                'filters' => $request->all(['page', 'size']),
                'errors' => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (\Throwable $e) {
            $resp = Inertia::render('channel/index', [
                'channels' => [],
                'filters' => $request->all(['page', 'size']),
                'errors' =>  ['error' => 'Internal server error'],
                ]
            );
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function search(SearchPaginationRequest $request)
    {
        try {
            $channels = $this->channelsService->search($request);

            return Inertia::render('channel/index', [
                'channels' => $channels,
                'filters' => $request->all(['search','page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('channel/index', [
                'channels' => [],
                'filters' => $request->all(['search','page', 'size']),
                'errors' => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (\Throwable $e) {
            $resp = Inertia::render('channel/index', [
                    'channels' => [],
                    'filters' => $request->all(['search','page', 'size']),
                    'errors' =>  ['error' => 'Internal server error'],
                ]
            );
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user) {

                Auth::guard('web')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'User must be logged in.');
            }

            if (!$user->hasPermission("channels", "create")) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create channel.');
            };
            return Inertia::render('channel/create');
        } catch (\Throwable $e) {
            return redirect()->route('channels.index')->with('error', 'Internal server error');
        }
    }

    public function store(CreateChannelRequest $request): RedirectResponse
    {
        try {
            $this->channelsService->create($request);
            return redirect()->route('channels.index')->with('message', 'Channel sucessfully created.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to create channel please try again.')->withInput();
        }
    }

    public function show(int $id)
    {
        try {
            $channel = $this->channelsService->getById($id);
            return Inertia::render('channel/show', [
                'channel' => $channel,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('channels.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('channels.index')->with('error', 'Internal server error, Failed to show channel please try again.');
        }
    }

    public function edit(int $id)
    {
        try {
            $channel = $this->channelsService->getById($id);
            return Inertia::render('channel/edit', [
                'channel' => $channel,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('channels.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('channels.index')->with('error', 'Internal server error.');
        }
    }

    public function update(UpdateChannelRequest $request, int $id): RedirectResponse
    {
        try {
            $this->channelsService->update($id, $request);
            return redirect()->route('channels.index')->with('message', 'Channel successfully updated.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        }  catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to update channel please try again.')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->channelsService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
