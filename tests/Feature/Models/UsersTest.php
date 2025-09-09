<?php

use App\Constants\RolesTypes;
use App\Models\Users;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can simulate rbac from users', function () {
    $this->seed(DatabaseSeeder::class);
    $users = Users::query()->with(['role', 'role.permissions'])->get();
    $admin = $users->where('role.name', RolesTypes::ALMIGHTY)->first();
    $this->assertTrue($admin->hasPermission('configurations', 'create'));
});
