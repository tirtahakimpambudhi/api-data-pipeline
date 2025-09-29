<?php

namespace Database\Factories;

use App\Models\Environments;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Services;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RolesPermissions>
 */
class RolesPermissionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn (array $attrs) =>
                $attrs['role_id'] ?? Roles::factory(),

            'permission_id' => fn (array $attrs) =>
                $attrs['permission_id'] ?? Permissions::factory(),
        ];
    }
}
