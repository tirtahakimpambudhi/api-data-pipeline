<?php

namespace App\Http\Controllers;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Service\Contracts\RolesService;
use App\Service\Contracts\UsersService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        protected UsersService $usersService,
        protected RolesService $rolesService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PaginationRequest $request)
    {
        try {
            $users = $this->usersService->getAll($request);
            return Inertia::render('users/index', [
                'users' => $users,
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
            $users = $this->usersService->search($request);
            return Inertia::render('users/index', [
                'users' => $users,
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
            if (!$user->hasPermission(ResourcesTypes::USERS, ActionsTypes::CREATE) || !$user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::READ) ) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to create users.');
            };
            $roles = $this->rolesService->getAll(null, true);
            return Inertia::render('users/create', [
                'roles' => $roles,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('users.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('users.index')->with('error', 'Internal server error');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        try {
            $this->usersService->create($request);
            return redirect()->route('users.index')->with('message', 'User successfully created.');
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
    public function show(string $id)
    {
        try {
            $user = $this->usersService->getById($id);
            return Inertia::render('users/detail', [
                'user' => $user,
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
    public function edit(string $id)
    {
        try {
            $user = Auth::guard('web')->user();
            if ((!$user->hasPermission(ResourcesTypes::USERS, ActionsTypes::UPDATE) || !$user->hasPermission(ResourcesTypes::ROLES, ActionsTypes::READ)) ) {
                return redirect()->route('dashboard')->with('error', 'User doesn\'t have permissions to update user.');
            };
            $roles = $this->rolesService->getAll(null, true);
            $user = $this->usersService->getById($id);
            return Inertia::render('users/edit', [
                'roles' => $roles,
                'user' => $user,
            ]);
        } catch (AppServiceException $e) {
            if ($redirect = $this->handleUnauthorizedAndPermissionDenied($e, request())) {
                return $redirect;
            }
            return redirect()->route('users.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->route('users.index')->with('error', 'Internal server error');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        try {
            $this->usersService->update($id,$request);
            return redirect()->route('users.index')->with('message', 'User successfully updated.');
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
    public function destroy(string $id)
    {
        try {
            $this->usersService->delete($id);
            return response()->json(null, 204);
        } catch (AppServiceException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
