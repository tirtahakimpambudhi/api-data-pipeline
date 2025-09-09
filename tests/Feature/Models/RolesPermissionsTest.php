<?php

use App\Models\Permissions;
use App\Models\Roles;
use App\Models\RolesPermissions;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can create a roles_permissions mapping and read relations', function () {
    $role = Roles::query()->create(['name' => 'Operator']);
    $perm = Permissions::query()->create(['resource_type' => 'service', 'action' => 'read']);

    $rp = RolesPermissions::query()->create([
        'role_id'       => $role->id,
        'permission_id' => $perm->id,
    ])->load(['role', 'permission']);

    expect($rp->role->name)->toBe('Operator')
        ->and($rp->permission->resource_type)->toBe('service')
        ->and($rp->permission->action)->toBe('read');
});


it('can update a roles_permissions mapping', function () {
    $role = Roles::query()->create(['name' => 'Dev']);
    $p1 = Permissions::query()->create(['resource_type' => 'service', 'action' => 'read']);
    $p2 = Permissions::query()->create(['resource_type' => 'service', 'action' => 'write']);

    $rp = RolesPermissions::query()->create([
        'role_id'       => $role->id,
        'permission_id' => $p1->id,
    ]);

    $rp->permission()->associate($p2)->save();

    expect($rp->permission_id)->toBe($p2->id);
    $rp->load('permission');
    expect($rp->permission->action)->toBe('write');
});

it('can delete a roles_permissions mapping', function () {
    $role = Roles::query()->create(['name' => 'QA']);
    $perm = Permissions::query()->create(['resource_type' => 'namespace', 'action' => 'manage']);

    $rp = RolesPermissions::query()->create([
        'role_id'       => $role->id,
        'permission_id' => $perm->id,
    ]);

    $id = $rp->id;
    $rp->delete();

    $this->assertDatabaseMissing('roles_permissions', ['id' => $id]);

    $role->load('permissions');
    expect($role->permissions->where('id', $perm->id))->toHaveCount(0);

    $perm->load('roles');
    expect($perm->roles->where('id', $role->id))->toHaveCount(0);
});
