<?php

use App\Models\Permissions;
use App\Models\Roles;
use Illuminate\Support\Facades\DB;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can create, update, and delete a permission', function () {
    // CREATE
    $perm = Permissions::query()->create(['resource_type' => 'namespace', 'action' => 'read']);
    expect($perm->resource_type)->toBe('namespace')
        ->and($perm->action)->toBe('read');

    // UPDATE
    $perm->update(['action' => 'manage']);
    expect($perm->fresh()->action)->toBe('manage');

    // DELETE
    $id = $perm->id;
    $perm->delete();
    $this->assertDatabaseMissing('permissions', ['id' => $id]);
});

it('can read with include all relationships permission', function () {
    $perm = Permissions::query()->create(['resource_type' => 'namespace', 'action' => 'manage']);
    $r1 = Roles::query()->create(['name' => 'Admin']);
    $r2 = Roles::query()->create(['name' => 'Developer']);

    $perm->roles()->attach([$r1->id, $r2->id]);

    $perm = $perm->fresh()->load(['roles', 'rolesPermissions']);

    expect($perm->roles)->not->toBeEmpty();

    if (method_exists($perm, 'rolesPermissions')) {
        expect($perm->rolesPermissions)->toHaveCount(2);
    }
});



it('can create, update, and delete a permission with roles models', function () {
    // CREATE: permission + beberapa role
    $perm = Permissions::query()->create(['resource_type' => 'namespace', 'action' => 'manage']);
    $r1 = Roles::query()->create(['name' => 'Admin']);
    $r2 = Roles::query()->create(['name' => 'Developer']);

    // Kaitkan roles ke permission
    $perm->roles()->attach([$r1->id, $r2->id]);
    $perm->load('roles');

    expect($perm->roles)->toHaveCount(2)
        ->and($perm->roles->pluck('id')->sort()->values()->all())
        ->toBe([$r1->id, $r2->id]);

    // UPDATE: ubah set role (sisakan Admin saja)
    $perm->roles()->sync([$r1->id]);
    $perm->refresh()->load('roles');

    expect($perm->roles)->toHaveCount(1)
        ->and($perm->roles->first()->id)->toBe($r1->id);

    // DELETE: hapus permission → pastikan baris pivot bersih
    $pivotTable = $perm->roles()->getTable();
    $permId = $perm->id;

    $perm->delete();

    $exists = DB::table($pivotTable)->where('permission_id', $permId)->exists();
    expect($exists)->toBeFalse();
});

