<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\InternalServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Environments\CreateEnvironmentRequest;
use App\Http\Requests\Environments\UpdateEnvironmentRequest;
use App\Service\Contracts\EnvironmentsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

    public function index(PaginationRequest $request)
    {
        try {
            $environments = $this->environmentsService->getAll($request);
            return Inertia::render('environment/index', [
                'environments' => $environments,
                'filters' => $request->all(['page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('environment/index', [
                'environments' => [],
                'filters'    => $request->all(['page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('environment/index', [
                'environments' => [],
                'filters'    => $request->all(['page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
            ]);
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function search(SearchPaginationRequest $request)
    {
        try {
            $environments = $this->environmentsService->search($request);
            return Inertia::render('environment/index', [
                'environments' => $environments,
                'filters' => $request->all(['search','page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('environment/index', [
                'environments' => $this->emptyPaginated(),
                'filters'    => $request->all(['search','page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
            ]);
            return $this->inertiaWithStatus($resp, $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('environment/index', [
                'environments' => $this->emptyPaginated(),
                'filters'    => $request->all(['search','page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
            ]);
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

            if (!$user->hasPermission(ResourcesTypes::ENVIRONMENTS, ActionsTypes::CREATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create environment.');
            };
            return Inertia::render('environment/create');
        } catch (\Throwable $e) {
            return redirect()->route('environments.index')->with('error', 'Internal server error.');
        }
    }

    public function store(CreateEnvironmentRequest $request): RedirectResponse
    {
        try {
            $this->environmentsService->create($request);
            return redirect()->route('environments.index')->with('message', 'Environment successfully created.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to create environment please try again')->withInput();
        }
    }

    public function show(int $id)
    {
        try {
            $environment = $this->environmentsService->getById($id);
            return Inertia::render('environment/show', [
                'environment' => $environment,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route("environments.index")->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route("environments.index")->with('error', 'Internal server error, Failed to show environment please try again.');
        }
    }

    public function edit(int $id)
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user->hasPermission(ResourcesTypes::ENVIRONMENTS, ActionsTypes::UPDATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update environments.');
            };
            $environment = $this->environmentsService->getById($id);
            return Inertia::render('environment/edit', [
                'environment' => $environment,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route("environments.index")->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route("environments.index")->with('error', 'Internal server error.');
        }
    }

    public function update(UpdateEnvironmentRequest $request, int $id): RedirectResponse
    {
        try {
            $this->environmentsService->update($id, $request);
            return redirect()->route('environments.index')->with('message', 'Environment successfully updated.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        }  catch (Throwable $e) {
            return back()->with('error', 'Internal server error, Failed to update environment please try again')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->environmentsService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
