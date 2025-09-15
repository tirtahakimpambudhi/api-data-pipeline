<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Service\Contracts\NamespacesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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


    public function index(PaginationRequest $request)
    {
        try {
            $namespaces = $this->namespacesService->getAll($request);
            return Inertia::render('namespace/index', [
                'namespaces' => $namespaces,
                'filters'    => $request->all(['page', 'size']),
                'errors'     => null,
                'serverError'=> null,
                'statusCode' => 200,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('namespace/index', [
                'namespaces' => $this->emptyPaginated(),
                'filters'    => $request->all(['page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
                'serverError'=> $e->getMessage(),
                'statusCode' =>  $e->getCode(),
            ]);
            return $this->inertiaWithStatus($resp,  $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('namespace/index', [
                'namespaces' => $this->emptyPaginated(),
                'filters'    => $request->all(['page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
                'serverError'=> config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'statusCode' => 500,
            ]);
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function search(SearchPaginationRequest $request)
    {
        try {
            $namespaces = $this->namespacesService->search($request);

            return Inertia::render('namespace/index', [
                'namespaces' => $namespaces,
                'filters'    => $request->all(['search', 'page', 'size']),
                'errors'     => null,
                'serverError'=> null,
                'statusCode' => 200,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            $resp = Inertia::render('namespace/index', [
                'namespaces' => $this->emptyPaginated(),
                'filters'    => $request->all(['search', 'page', 'size']),
                'errors'     => method_exists($e, 'toMessageBag') ? $e->toMessageBag()->toArray() : ['error' => [$e->getMessage()]],
                'serverError'=> $e->getMessage(),
                'statusCode' => $e->getCode(),
            ]);
            return $this->inertiaWithStatus($resp,  $e->getCode());
        } catch (Throwable $e) {
            $resp = Inertia::render('namespace/index', [
                'namespaces' => $this->emptyPaginated(),
                'filters'    => $request->all(['search', 'page', 'size']),
                'errors'     => ['error' => ['Internal server error.']],
                'serverError'=> config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'statusCode' => 500,
            ]);
            return $this->inertiaWithStatus($resp, 500);
        }
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user) {

                Auth::guard('web')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'User must be logged in.');
            }

            if (!$user->hasPermission("namespaces", "create")) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create namespaces.');
            };

            return Inertia::render('namespace/create');
        } catch (Throwable $e) {
            return redirect()->route('namespaces.index')->with('error', 'Internal server error.');
        }
    }

    public function store(CreateNamespaceRequest $request): RedirectResponse
    {
        try {
            $this->namespacesService->create($request);
            return redirect()->route('namespaces.index')->with('message', 'Namespaces created successfully.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        }  catch (Throwable $e) {
            return back()->with('error', 'Internal server error, please try again create namespace : ' . $e->getMessage())->withInput();
        }
    }


    public function show(int $id): Response | RedirectResponse
    {
        try {
            $namespace = $this->namespacesService->getById($id);
            return Inertia::render('namespace/show', ['namespace' => $namespace]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, please try again show namespace : ' . $e->getMessage())->withInput();
        }
    }


    public function edit(int $id): Response | RedirectResponse
    {
        try {
            $user = Auth::guard('web')->user();
            if (!$user->hasPermission(ResourcesTypes::NAMESPACES, ActionsTypes::UPDATE)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update namespaces.');
            };
            $namespace = $this->namespacesService->getById($id);
            return Inertia::render('namespace/edit', ['namespace' => $namespace]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, please try again edit namespace : ' . $e->getMessage())->withInput();
        }
    }

    public function update(UpdateNamespaceRequest $request, int $id): RedirectResponse
    {
        try {

            $this->namespacesService->update($id, $request);
            return redirect()->route('namespaces.index')->with('message', 'Namespace updated successfully.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                return back()->with('error', $e->getMessage());
            }

            if ($e instanceof ConflictServiceException) {
                return back()->withErrors(['name' => $e->getMessage()])->with('error', $e->getMessage())->withInput();
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, please try again update namespace : ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->namespacesService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function storeService(CreateServiceRequest $request, int $namespaceId): RedirectResponse
    {
        try {
            $this->namespacesService->createService($namespaceId, $request);
            return redirect()->route('namespaces.show', $namespaceId)->with('message', 'Service successfully created.');
        }catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                return back()->with('error', $e->getMessage());
            }

            if ($e instanceof ConflictServiceException) {
                return back()->withErrors(['name' => $e->getMessage()])->withInput();
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Internal server error, please try again create service with namespace : ' . $e->getMessage() )->withInput();
        }
    }
}
