<?php

use App\Constants\RolesTypes;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can simulate rbac from users', function () {
    $role = Roles::create(['name' => RolesTypes::ALMIGHTY]);

    $perm = Permissions::create([
        'resource_type' => 'configurations',
        'action'        => 'create',
    ]);

    $role->permissions()->attach($perm->id);

    $admin = Users::factory()->for($role, 'role')->create();

    $admin->load('role.permissions');

    $this->assertTrue($admin->hasPermission('configurations', 'create'));
});
