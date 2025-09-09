<?php

use App\Models\Permissions;
use App\Models\Roles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
    $this->seed(DatabaseSeeder::class);

    $role = Roles::query()
        ->with('permissions')
        ->with('rolesPermissions')
        ->with('users')
        ->findOrFail(1);

    $allPermissions = $role->permissions->all();
    $allUsers = $role->users->all();
    expect($allPermissions)->not->toBeEmpty();
    expect($allUsers)->not->toBeEmpty();
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
