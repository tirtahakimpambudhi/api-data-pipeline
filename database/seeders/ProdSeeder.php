<?php

namespace Database\Seeders;

use App\Constants\ActionsTypes;
use App\Constants\ChannelsTypes;
use App\Constants\EnvironmentsTypes;
use App\Constants\ResourcesTypes;
use App\Constants\RolesTypes;
use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\RolesPermissions;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use App\Models\Users;
use App\Traits\Helpers;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProdSeeder extends Seeder
{
    use Helpers;

    private function seedPermissions(int $count): array
    {
        $pairs = $this->crossComboArr(
            ResourcesTypes::all(),
            ActionsTypes::all(),
            'resource_type',
            'action',
        );

        $selectedPairs = array_slice($pairs, 0, min($count, count($pairs)));

        $permissions = [];
        foreach ($selectedPairs as $pair) {
            $permissions[] = [
                'resource_type' => $pair['resource_type'],
                'action'        => $pair['action'],
                'description'   => 'Example Description',
            ];
        }

        return $permissions;
    }


    function seedRolesPermissions(int $count, array $permissionsIds,array $roleId): array
    {
        $pairs = $this->crossComboArr($permissionsIds, $roleId, 'permission_id', 'role_id');

        $selectedPairs = array_slice($pairs, 0, min($count, count($pairs)));

        $rolesPermissions = [];
        foreach ($selectedPairs as $pair) {
            $rolesPermissions[] = [
                'permission_id' => $pair['permission_id'],
                'role_id'        => $pair['role_id'],
            ];
        }

        return $rolesPermissions;
    }

    public function run(): void
    {
        try {
            Schema::disableForeignKeyConstraints();
            Services::truncate();
            Namespaces::truncate();
            Environments::truncate();
            Channels::truncate();
            RolesPermissions::truncate();
            Users::truncate();
            Roles::truncate();
            Permissions::truncate();
            Schema::enableForeignKeyConstraints();
        } catch (\Throwable $e) {
            echo "Error when reset all table: " . $e->getMessage() . "\n";
        }

        DB::beginTransaction();

        try {
            // 1. Seed roles
            $rolesData = [
                ['name' => RolesTypes::ALMIGHTY, 'description' => 'Can Read and Write All Features'],
                ['name' => RolesTypes::SLAVE,    'description' => 'Can Read and Write Configurations'],
            ];

            Roles::insert($rolesData);
            $roles = Roles::whereIn('name', [RolesTypes::ALMIGHTY, RolesTypes::SLAVE])->get();

            // 2. Seed channels
            $channelsData = [
                ['name' => ChannelsTypes::DISCORD],
                ['name' => ChannelsTypes::TELEGRAM],
                ['name' => ChannelsTypes::WHATSAPP],
            ];
            Channels::insert($channelsData);

            // 3. Seed environments
            $environmentsData = [
                ['name' => EnvironmentsTypes::PROD],
                ['name' => EnvironmentsTypes::DEV],
                ['name' => EnvironmentsTypes::STAGING],
                ['name' => EnvironmentsTypes::LOCAL],
                ['name' => EnvironmentsTypes::TEST],
            ];
            Environments::insert($environmentsData);

            // 4. Seed permissions
            $permissionsCount = count(ResourcesTypes::all()) * count(ActionsTypes::all());
            $permissionsData  = $this->seedPermissions($permissionsCount);

            Permissions::insert($permissionsData);
            $permissions = Permissions::get();

            // 5. Calculate role & permission IDs
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

            $almightyRoleId = $roles->firstWhere('name', RolesTypes::ALMIGHTY)?->id;
            $slaveRoleId    = $roles->firstWhere('name', RolesTypes::SLAVE)?->id;

            // 6. Seed roles_permissions
            $almightyRolePerms = $this->seedRolesPermissions(
                count($almightyPermissionsIds),
                $almightyPermissionsIds,
                [$almightyRoleId],
            );

            $slaveRolePerms = $this->seedRolesPermissions(
                count($slavePermissionIds),
                $slavePermissionIds,
                [$slaveRoleId],
            );

            RolesPermissions::insert($almightyRolePerms);
            RolesPermissions::insert($slaveRolePerms);

            // 7. Seed admin user
            $adminEmail    = env('ADMIN_EMAIL');
            $adminPassword = env('ADMIN_PASSWORD');
            $adminUsername = env('ADMIN_USERNAME');

            if ($adminEmail && $adminPassword && $adminUsername && $almightyRoleId) {
                Users::query()->create([
                    'role_id'  => $almightyRoleId,
                    'email'    => $adminEmail,
                    'password' => Hash::make($adminPassword),
                    'name'     => $adminUsername,
                ]);
            }

            DB::commit();
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            echo "Conflict (unique constraint): {$e->getMessage()}\n";
        } catch (\Throwable $e) {
            DB::rollBack();
            echo $e->getMessage() . "\n";
        }
    }

}

