<?php

namespace App\Http\Controllers;

use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Exceptions\ValidationServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Service\Contracts\NamespacesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;



class NamespaceController extends Controller
{
    protected NamespacesService $namespacesService;

    public function __construct(NamespacesService $namespacesService)
    {
        $this->namespacesService = $namespacesService;
    }

    private function emptyPaginated(): array
    {
        return [
            'data' => [],
            'meta' => [
                'total'        => 0,
                'per_page'     => 0,
                'current_page' => 1,
                'last_page'    => 1,
            ],
        ];
    }

    private function inertiaWithStatus(Response $resp, int $status)
    {
        return $resp->toResponse(request())->setStatusCode($status);
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
                'statusCode' => 422,
            ]);
            return $this->inertiaWithStatus($resp, 422);
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
                'statusCode' => 422,
            ]);
            return $this->inertiaWithStatus($resp, 422);
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
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'User must be logged in.');
        }

        if (!$user->hasPermission("namespaces", "create")) {
            return redirect()->route('login')->with('error', 'User doesn\'t have permissions.');
        };

        return Inertia::render('namespace/create');
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
            return back()->withErrors($e->getBags())->withInput();
        }  catch (Throwable $e) {
            return back()->with('error', 'Something wrong in internal, please try again create namespace : ' . $e->getMessage())->withInput();
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
            if ($e instanceof NotFoundServiceException) {
                abort(404, $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Something wrong in internal, please try again show namespace : ' . $e->getMessage())->withInput();
        }
    }


    public function edit(int $id): Response | RedirectResponse
    {
        try {
            $namespace = $this->namespacesService->getById($id);
            return Inertia::render('namespace/edit', ['namespace' => $namespace]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            if ($e instanceof NotFoundServiceException) {
                abort(404, $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Something wrong in internal, please try again edit namespace : ' . $e->getMessage())->withInput();
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
                return back()->withErrors(['name' => $e->getMessage()])->withInput();
            }
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return back()->with('error', 'Something wrong in internal, please try again update namespace : ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->namespacesService->delete($id);
            return redirect()->route('namespaces.index')->with('message', 'Namespace deleted successfully.');
        } catch (AppServiceException $e) {
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
            return back()->with('error', 'Something wrong in internal, please try again delete namespace : ' . $e->getMessage())->withInput();
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
            return back()->with('error', 'Something wrong in internal, please try again create service with namespace : ' . $e->getMessage() )->withInput();
        }
    }
}
