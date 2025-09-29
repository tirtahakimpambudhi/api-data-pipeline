<?php

namespace App\Http\Controllers\Auth;

use App\Constants\RolesTypes;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmRegisterAdmin;
use App\Models\PendingAdminRegistrations;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Users;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredAdminController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('admin/register');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name'     => ['required','string','max:255'],
                'email'    => ['required','string','lowercase','email','max:255','unique:users,email', 'unique:pending_admin_registrations,email'],
                'password' => ['required','confirmed', Rules\Password::defaults()],
            ]);

            $pending = PendingAdminRegistrations::create([
                'id' => Str::ulid()->toString(),
                'name'          => $validated['name'],
                'email'         => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'role_name'     => RolesTypes::ALMIGHTY,
                'nonce'         => Str::uuid()->toString(),
                'expires_at'    => now()->addDays(2),
            ]);

            $developerEmail = env('MAIL_FROM_ADDRESS');
            if (!$developerEmail) {
                return to_route('admin.register.form')->with('error','MAIL_FROM_ADDRESS not configured.');
            }
            Mail::to($developerEmail)->send(new ConfirmRegisterAdmin($pending));

            return to_route('admin.register.form')->with('message', 'Registration submitted. Waiting for developer approval via email.');
        } catch (QueryException $exception){
            return to_route('admin.register.form')->with('error', $exception->getMessage());
        } catch (\Throwable $exception){
            return to_route('admin.register.form')->with('error', $exception->getMessage());
        }
    }

    public function approve(Request $request): RedirectResponse
    {
        $id    = (string) $request->query('id');
        $nonce = (string) $request->query('nonce');

        /** @var PendingAdminRegistrations $pending */
        $pending = PendingAdminRegistrations::query()->findOrFail($id);

        if ($pending->nonce !== $nonce) {
            abort(403, 'Invalid nonce.');
        }
        if ($pending->isExpired()) {
            abort(410, 'Link expired.');
        }
        if ($pending->isFinalized()) {
            return redirect()->route('admin.register.form')->with('message', 'Already finalized.');
        }

        DB::transaction(function () use ($pending) {
            /** @var Roles $role */
            $role = Roles::query()->firstOrCreate(
                ['name' => $pending->role_name],
                ['name' => $pending->role_name, 'description' => 'Full access role']
            );

            $permSpecs = RolesTypes::permissions($pending->role_name);
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

            // Create user final
            /** @var Users $user */
            $user = Users::create([
                'name'     => $pending->name,
                'email'    => $pending->email,
                'password' => $pending->password_hash,
                'role_id'  => $role->getKey(),
            ]);

            event(new Registered($user));

            // finalize pending
            $pending->approved_at = now();
            $pending->save();
        });

        return redirect()->route('admin.register.form')->with('message', 'Admin registration approved and created.');
    }

    public function reject(Request $request): RedirectResponse
    {
        $id    = (string) $request->query('id');
        $nonce = (string) $request->query('nonce');

        /** @var PendingAdminRegistrations $pending */
        $pending = PendingAdminRegistrations::query()->findOrFail($id);

        if ($pending->nonce !== $nonce) {
            abort(403, 'Invalid nonce.');
        }
        if ($pending->isExpired()) {
            abort(410, 'Link expired.');
        }
        if ($pending->isFinalized()) {
            return redirect()->route('admin.register.form')->with('message', 'Already finalized.');
        }

//        $pending->rejected_at = now();
//        $pending->save();
        $pending->delete();

        return redirect()->route('admin.register.form')->with('message', 'Admin registration rejected.');
    }
}
