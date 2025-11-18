<?php

namespace App\Console\Commands;

use App\Constants\ActionsTypes;
use App\Constants\ChannelsTypes;
use App\Constants\EnvironmentsTypes;
use App\Constants\ResourcesTypes;
use App\Constants\RolesTypes;
use App\Models\Channels;
use App\Models\Environments;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\RolesPermissions;
use App\Models\Users;
use App\Traits\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AppSetup extends Command
{
    use Helpers;

    protected $signature   = 'app:setup';
    protected $description = 'Setup application: migrate & create default data if missing';

    public function handle(): int
    {
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());

        $this->info('Checking and creating default data if needed...');

        DB::beginTransaction();

        try {
            $this->ensureRolesExist();
            $this->ensureChannelsExist();
            $this->ensureEnvironmentsExist();
            $this->ensurePermissionsExist();
            $this->ensureRolesPermissionsExist();
            $this->ensureAdminUserExist();

            DB::commit();
            $this->info('App setup completed.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('App setup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    // ================== HELPERS ==================

    private function ensureRolesExist(): void
    {
        Roles::updateOrCreate(
            ['name' => RolesTypes::ALMIGHTY],
            ['description' => 'Can Read and Write All Features'],
        );

        Roles::updateOrCreate(
            ['name' => RolesTypes::SLAVE],
            ['description' => 'Can Read and Write Configurations'],
        );

        $this->info('- Roles checked.');
    }

    private function ensureChannelsExist(): void
    {
        $channels = [
            ChannelsTypes::DISCORD,
            ChannelsTypes::TELEGRAM,
            ChannelsTypes::WHATSAPP,
        ];

        foreach ($channels as $name) {
            Channels::firstOrCreate(['name' => $name]);
        }

        $this->info('- Channels checked.');
    }

    private function ensureEnvironmentsExist(): void
    {
        $envs = [
            EnvironmentsTypes::PROD,
            EnvironmentsTypes::DEV,
            EnvironmentsTypes::STAGING,
            EnvironmentsTypes::LOCAL,
            EnvironmentsTypes::TEST,
        ];

        foreach ($envs as $name) {
            Environments::firstOrCreate(['name' => $name]);
        }

        $this->info('- Environments checked.');
    }

    private function ensurePermissionsExist(): void
    {
        $pairs = $this->crossComboArr(
            ResourcesTypes::all(),
            ActionsTypes::all(),
            'resource_type',
            'action',
        );

        foreach ($pairs as $pair) {
            Permissions::firstOrCreate(
                [
                    'resource_type' => $pair['resource_type'],
                    'action'        => $pair['action'],
                ],
                [
                    'description'   => 'Example Description',
                ],
            );
        }

        $this->info('- Permissions checked.');
    }

    private function ensureRolesPermissionsExist(): void
    {
        $roles       = Roles::whereIn('name', [RolesTypes::ALMIGHTY, RolesTypes::SLAVE])->get();
        $permissions = Permissions::all();

        $almightyRoleId = $roles->firstWhere('name', RolesTypes::ALMIGHTY)?->id;
        $slaveRoleId    = $roles->firstWhere('name', RolesTypes::SLAVE)?->id;

        if (! $almightyRoleId || ! $slaveRoleId) {
            $this->warn('- Roles not complete, skipping roles_permissions.');
            return;
        }

        $almightyPermissionsIds = $permissions->filter(function ($value) {
            return in_array($value->resource_type, [
                ResourcesTypes::NAMESPACES,
                ResourcesTypes::SERVICES,
                ResourcesTypes::CHANNELS,
                ResourcesTypes::ENVIRONMENTS,
                ResourcesTypes::SERVICES_ENVIRONMENTS,
                ResourcesTypes::CONFIGURATIONS,
            ]);
        })->pluck('id')->all();

        $slavePermissionIds = $permissions->filter(function ($value) {
            return
                $value->resource_type == ResourcesTypes::CONFIGURATIONS ||
                ($value->resource_type == ResourcesTypes::SERVICES_ENVIRONMENTS && $value->action == ActionsTypes::READ) ||
                ($value->resource_type == ResourcesTypes::CHANNELS && $value->action == ActionsTypes::READ);
        })->pluck('id')->all();

        // Almighty role → all almightyPermissionsIds
        foreach ($almightyPermissionsIds as $permId) {
            RolesPermissions::firstOrCreate(
                [
                    'role_id'       => $almightyRoleId,
                    'permission_id' => $permId,
                ],
            );
        }

        // Slave role → subset permissions
        foreach ($slavePermissionIds as $permId) {
            RolesPermissions::firstOrCreate(
                [
                    'role_id'       => $slaveRoleId,
                    'permission_id' => $permId,
                ],
            );
        }

        $this->info('- RolesPermissions checked.');
    }

    private function ensureAdminUserExist(): void
    {
        $adminEmail    = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminUsername = env('ADMIN_USERNAME');

        if (! $adminEmail || ! $adminPassword || ! $adminUsername) {
            $this->warn('- ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_USERNAME not set, skipping admin user.');
            return;
        }

        $almightyRoleId = Roles::where('name', RolesTypes::ALMIGHTY)->value('id');

        if (! $almightyRoleId) {
            $this->warn('- Almighty role not found, skipping admin user.');
            return;
        }

        Users::updateOrCreate(
            ['email' => $adminEmail],
            [
                'role_id'  => $almightyRoleId,
                'name'     => $adminUsername,
                'password' => Hash::make($adminPassword),
            ],
        );

        $this->info('- Admin user checked.');
    }
}
