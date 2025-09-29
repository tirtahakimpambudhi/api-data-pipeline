<?php

use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Users;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create, update, and delete a role', function () {
    // CREATE
    $role = Roles::query()->create(['name' => 'role', 'description' => 'description']);
    expect($role->name)->toBe('role');

    // UPDATE
    $role->update(['name' => 'role-2']);
    expect($role->fresh()->name)->toBe('role-2');

    // DELETE
    $id = $role->id;
    $role->delete();

    $this->assertDatabaseMissing('roles', ['id' => $id]);
});

it('can read with include all relationships role', function () {
    $role = Roles::create(['name' => 'Admin', 'description' => 'administrator']);

    $p1 = Permissions::create(['resource_type' => 'configurations', 'action' => 'create']);
    $p2 = Permissions::create(['resource_type' => 'configurations', 'action' => 'read']);
    $role->permissions()->attach([$p1->id, $p2->id]);

    $u1 = Users::factory()->for($role, 'role')->create();
    $u2 = Users::factory()->for($role, 'role')->create();

    $role->load(['permissions','rolesPermissions','users']);

    expect($role->permissions)->toHaveCount(2);
    expect($role->users)->toHaveCount(2);
    if (method_exists($role, 'rolesPermissions')) {
        expect($role->rolesPermissions)->toHaveCount(2);
    }
});


it('can create, update, and delete a role with permissions models', function () {
    // CREATE: role + beberapa permission
    $role = Roles::query()->create(['name' => 'Operator']);
    $p1 = Permissions::query()->create(['resource_type' => 'service', 'action' => 'read']);
    $p2 = Permissions::query()->create(['resource_type' => 'service', 'action' => 'write']);
    $p3 = Permissions::query()->create(['resource_type' => 'service', 'action' => 'delete']);

    // Kaitkan permissions ke role (attach)
    $role->permissions()->attach([$p1->id, $p2->id]);
    $role->load('permissions');

    expect($role->permissions)->toHaveCount(2)
        ->and($role->permissions->pluck('id')->sort()->values()->all())
        ->toBe([$p1->id, $p2->id]);

    if (method_exists($role, 'rolesPermissions')) {
        $role->load('rolesPermissions');
        expect($role->rolesPermissions)->toHaveCount(2);
    }

    // UPDATE: rename role + ubah set permission
    $role->update(['name' => 'Operator v2']);
    $role->permissions()->sync([$p1->id, $p3->id]); // p2 keluar, p3 masuk
    $role->refresh()->load('permissions');

    expect($role->name)->toBe('Operator v2')
        ->and($role->permissions->pluck('id')->sort()->values()->all())
        ->toBe([$p1->id, $p3->id]);

    if (method_exists($role, 'rolesPermissions')) {
        $role->load('rolesPermissions');
        expect($role->rolesPermissions)->toHaveCount(2);
    }

    // DELETE: hapus role → pastikan baris pivot bersih
    $pivotTable = $role->permissions()->getTable(); // nama tabel pivot aktual
    $roleId = $role->id;

    $role->delete();

    $exists = DB::table($pivotTable)->where('role_id', $roleId)->exists();
    expect($exists)->toBeFalse();
});
