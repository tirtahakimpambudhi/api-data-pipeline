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
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DevSeeder extends Seeder
{
    use Helpers;
    public function run(Faker $faker): void
    {
        try {
            Schema::disableForeignKeyConstraints();
            Services::truncate();
            Namespaces::truncate();
            Environments::truncate();
            Channels::truncate();
            Namespaces::truncate();
            Roles::truncate();
            Permissions::truncate();
            Schema::enableForeignKeyConstraints();
        } catch (\Throwable $e) {
            DB::rollBack();
            echo "Error when reset all table: ". $e->getMessage() . "\n";
        }

        DB::beginTransaction();

        try {
            // TODO: make helper function to create new sequences
            $roles = Roles::factory()
                ->count(count(RolesTypes::all()))
                ->state(new Sequence(
                    ['name' => RolesTypes::ALMIGHTY],
                    ['name' => RolesTypes::SLAVE],
                ))
                ->create();

            $channels = Channels::factory()
                ->count(count(ChannelsTypes::all()))
                ->state(new Sequence(
                    ['name' => ChannelsTypes::DISCORD],
                    ['name' => ChannelsTypes::TELEGRAM],
                    ['name' => ChannelsTypes::WHATSAPP],
                ))
                ->create();

            $environments = Environments::factory()
                ->count(count(EnvironmentsTypes::all()))
                ->state(new Sequence(
                    ['name' => EnvironmentsTypes::PROD],
                    ['name' => EnvironmentsTypes::DEV],
                    ['name' => EnvironmentsTypes::STAGING],
                    ['name' => EnvironmentsTypes::LOCAL],
                    ['name' => EnvironmentsTypes::TEST],
                ))
                ->create();
            $namespaces = Namespaces::factory()
                ->count(3)
                ->state(
                    new Sequence(
                        [ 'name' => 'master'],
                        [ 'name' => 'worker-one'],
                        [ 'name' => 'worker-two']
                    )
                )
                ->create();

            $namespacesIds = $namespaces->pluck('id')->all();

            $services = Services::factory()
                ->count(4)
                ->state(
                    new Sequence(
                        ...$this->randomOneWayComboArr(['payment-gateway', 'svd','api-aggregator', 'api-merchant'], $namespacesIds, 'name', 'namespace_id')
                    )
                )
                ->create();
            $servicesIds = $services->pluck('id')->all();
            $environmentsIds = $environments->pluck('id')->all();

            $servicesEnvironments = ServicesEnvironments::factory()
                ->count(count($servicesIds) * count($environmentsIds))
                ->state(new Sequence(
                    ...$this->crossComboArr($servicesIds, $environmentsIds, 'service_id', 'environment_id')
                ))
                ->create();
            $servicesEnvironmentsIds = $servicesEnvironments->pluck('id')->all();
            $channelsIds = $channels->pluck('id')->all();
            $configurations = Configurations::factory()
                ->count(count($servicesEnvironmentsIds) * count($channelsIds))
                ->state(new Sequence(
                    ...$this->crossComboArr($servicesEnvironmentsIds, $channelsIds, 'service_environment_id', 'channel_id')
                ))
                ->create();


            $permissionsCount = count(ResourcesTypes::all()) * count(ActionsTypes::all());
            $permissions = Permissions::factory($permissionsCount)->create();


            $almightyPermissionsIds = $permissions->filter(function ($value, $key) {
                return in_array($value->resource_type, [ResourcesTypes::NAMESPACES, ResourcesTypes::SERVICES, ResourcesTypes::CHANNELS, ResourcesTypes::ENVIRONMENTS, ResourcesTypes::SERVICES_ENVIRONMENTS, ResourcesTypes::CONFIGURATIONS]);
            } )->pluck('id')->all();

            $almightyRoleId= $roles->filter(function ($value, $key) {
                return $value->name == RolesTypes::ALMIGHTY;
            } )->pluck('id')->all();

            $slaveRoleId= $roles->filter(function ($value, $key) {
                return $value->name == RolesTypes::SLAVE;
            } )->pluck('id')->all();

            $slavePermissionIds = $permissions->filter(function ($value, $key) {
                return $value->resource_type == ResourcesTypes::CONFIGURATIONS || ($value->resource_type == ResourcesTypes::SERVICES_ENVIRONMENTS && $value->action == ActionsTypes::READ) || ($value->resource_type == ResourcesTypes::CHANNELS && $value->action == ActionsTypes::READ);
            } )->pluck('id')->all();


            RolesPermissions::factory()
                ->count(count($almightyPermissionsIds))
                ->state(new Sequence(
                    ...$this->crossComboArr($almightyPermissionsIds, $almightyRoleId, 'permission_id', 'role_id')
                ))
                ->create();

            RolesPermissions::factory()
                ->count(count($slavePermissionIds))
                ->state(new Sequence(
                    ...$this->crossComboArr($slavePermissionIds, $slaveRoleId, 'permission_id', 'role_id')
                ))
                ->create();

            Users::factory(4)->state(['role_id' => $slaveRoleId[0]])->create();
            Users::factory(1)->state(['role_id' => $almightyRoleId[0]])->create();
            Users::factory(1)->state(['role_id' => $almightyRoleId[0], 'email' => 'admin@gmail.com', 'password' => Hash::make("admin1234")])->create();
            Users::factory(1)->state(['role_id' => $slaveRoleId[0], 'email' => 'user@gmail.com', 'password' => Hash::make("user1234")])->create();
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
