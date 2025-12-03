<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Roles\CreateRolesRequest;
use App\Http\Requests\Roles\UpdateRolesRequest;
use App\Service\Contracts\PermissionsService;
use App\Service\Contracts\RolesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class RoleController extends Controller
{

    public function __construct(
        protected RolesService $rolesService,
        protected PermissionsService $permissionsService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PaginationRequest $request)
    {
        try {
            $roles = $this->rolesService->getAll($request);
            return Inertia::render('roles/index', [
                'roles' => $roles,
                'filters' => $request->all(['page', 'size']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Internal server error');
        }
    }

    public function search(SearchPaginationRequest $request)
    {
        try {
            $roles = $this->rolesService->search($request);
            return Inertia::render('roles/index', [
                'roles' => $roles,
                'filters' => $request->all(['page', 'size', 'search']),
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, $request)) {
                return $redirect;
            }
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Internal server error');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $user = Auth::guard('web')->user();

            if (!$user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::CREATE) || !$user->hasPermission(ResourcesTypes::ROLES_PERMISSIONS, ActionsTypes::CREATE) || !$user->hasPermission(ResourcesTypes::PERMISSIONS, ActionsTypes::READ)) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create roles.');
            };
            $permissions = $this->permissionsService->getAll(null, true);
            return Inertia::render('roles/create', [
                'permissions' => $permissions,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('roles.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('roles.index')->with('error', 'Internal server error');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRolesRequest $request)
    {
        try {
            $this->rolesService->create($request);
            return redirect()->route('roles.index')->with('message', 'Role successfully created.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return  back()->with('error', $e->getMessage())->withInput()->with('error', 'Internal server error, Failed to create role please try again later.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            $role = $this->rolesService->getById($id);
            return Inertia::render('roles/detail', [
                'role' => $role,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Internal server error');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        try {
            $user = Auth::guard('web')->user();
            if ((!$user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::UPDATE) || !$user->hasPermission(ResourcesTypes::ROLES_PERMISSIONS, ActionsTypes::CREATE) || !$user->hasPermission(ResourcesTypes::PERMISSIONS, ActionsTypes::READ))) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update roles.');
            };
            $permissions = $this->permissionsService->getAll(null, true);
            $role = $this->rolesService->getById($id);
            return Inertia::render('roles/edit', [
                'permissions' => $permissions,
                'role' => $role,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('roles.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('roles.index')->with('error', 'Internal server error');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRolesRequest $request, int $id)
    {
        try {
            $this->rolesService->update($id,$request);
            return redirect()->route('roles.index')->with('message', 'Role successfully updated.');
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return  back()->with('error', $e->getMessage())->withInput()->with('error', 'Internal server error, Failed to update role please try again later.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            $this->rolesService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
