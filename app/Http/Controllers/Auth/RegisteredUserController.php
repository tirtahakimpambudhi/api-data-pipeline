<?php

namespace App\Http\Controllers\Auth;

use App\Constants\RolesTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Users;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function store(RegisterRequest $request): RedirectResponse
    {
        $value = $request->validated();

        /** @var Users $user */
        $user = DB::transaction(function () use ($value) {
            /** @var Roles $role */
            $role = Roles::query()->firstOrCreate(
                ['name' => RolesTypes::SLAVE],
                ['name' => RolesTypes::SLAVE, 'description' => 'Limited access role']
            );

            $permSpecs = RolesTypes::permissions(RolesTypes::SLAVE);

            $permissionIds = collect($permSpecs)->map(function (array $spec) {
                $perm = Permissions::query()->firstOrCreate(
                    [
                        'resource_type' => $spec['resource_type'],
                        'action'        => $spec['action'],
                    ],
                    [
                        'resource_type' => $spec['resource_type'],
                        'action'        => $spec['action'],
                        'description'   => $spec['description'] ?? null,
                    ]
                );
                return $perm->getKey();
            })->all();

            $role->permissions()->syncWithoutDetaching($permissionIds);

            $user = Users::create([
                'name'     => $value['name'],
                'email'    => $value['email'],
                'password' => Hash::make($value['password']),
                'role_id'  => $role->getKey(),
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
